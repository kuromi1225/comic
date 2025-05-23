from flask import Blueprint, render_template, flash, redirect, url_for, request
from app import db
from flask import current_app # To access app.config
from app.forms import LoginForm, RegistrationForm, AddComicForm, AddVolumeForm, UploadReceiptForm # Added UploadReceiptForm
from app.models import User, Comic, UserComicVolume
from flask_login import current_user, login_user, logout_user, login_required
import werkzeug.urls # Try importing the module directly
from app.services import get_new_releases_for_user, get_comics_with_missing_volumes 
from datetime import datetime 
import requests # For calling OCR service

bp = Blueprint('main', __name__)

@bp.route('/')
@bp.route('/index')
@login_required
def index():
    current_month = datetime.utcnow().month
    current_year = datetime.utcnow().year
    new_releases = get_new_releases_for_user(current_user, current_month, current_year)
    new_releases_count = len(new_releases)
    
    comics_with_missing = get_comics_with_missing_volumes(current_user)
    missing_volumes_count = len(comics_with_missing)
    
    return render_template('index.html', 
                           title='Home', 
                           new_releases_count=new_releases_count,
                           missing_volumes_count=missing_volumes_count)

@bp.route('/new_releases')
@login_required
def new_releases_list():
    current_month = datetime.utcnow().month
    current_year = datetime.utcnow().year
    new_releases = get_new_releases_for_user(current_user, current_month, current_year)
    month_name = datetime(current_year, current_month, 1).strftime('%B')
    return render_template('new_releases_list.html', 
                           title='New Releases', 
                           releases=new_releases, 
                           month_name=month_name, 
                           year=current_year)

@bp.route('/comics_with_missing_volumes')
@login_required
def comics_missing_volumes_list():
    comics_with_missing = get_comics_with_missing_volumes(current_user)
    return render_template('comics_missing_volumes_list.html', 
                           title='Comics with Missing Volumes', 
                           comics_list=comics_with_missing)

@bp.route('/upload_receipt', methods=['GET', 'POST'])
@login_required
def upload_receipt():
    form = UploadReceiptForm()
    extracted_isbns = None
    ocr_error = None

    if form.validate_on_submit():
        image_file = form.receipt_image.data
        
        files = {'file': (image_file.filename, image_file.stream, image_file.mimetype)}
        ocr_service_url = current_app.config.get('OCR_SERVICE_URL')

        if not ocr_service_url:
            flash('OCR Service URL is not configured.', 'error')
            return render_template('upload_receipt.html', title='Upload Receipt', form=form, extracted_isbns=None, ocr_error='OCR Service URL not configured.')

        try:
            response = requests.post(ocr_service_url, files=files, timeout=60) # Increased timeout
            response.raise_for_status()  # Raise an exception for bad status codes

            data = response.json()
            extracted_isbns = data.get('isbns', [])
            
            if extracted_isbns:
                flash(f"Successfully extracted ISBNs: {', '.join(extracted_isbns)}", 'success')
            else:
                flash('No ISBNs found in the image, or the OCR service could not extract them.', 'info')
        
        except requests.exceptions.ConnectionError:
            ocr_error = "Could not connect to the OCR service. Please ensure it's running."
            flash(ocr_error, 'error')
        except requests.exceptions.Timeout:
            ocr_error = "The request to the OCR service timed out."
            flash(ocr_error, 'error')
        except requests.exceptions.HTTPError as e:
            status_code = e.response.status_code
            try:
                error_detail = e.response.json().get('detail', 'Unknown error from OCR service.')
            except ValueError: # If response is not JSON
                error_detail = e.response.text if e.response.text else 'Unknown error from OCR service.'
            
            ocr_error = f"OCR service returned an error (Status {status_code}): {error_detail}"
            flash(ocr_error, 'error')
        except requests.exceptions.RequestException as e:
            ocr_error = f"An error occurred while communicating with the OCR service: {e}"
            flash(ocr_error, 'error')
        except Exception as e: # Catch-all for other unexpected errors
            ocr_error = f"An unexpected error occurred: {str(e)}"
            flash(ocr_error, 'error')
            
    return render_template('upload_receipt.html', 
                           title='Upload Receipt for ISBN Extraction', 
                           form=form, 
                           extracted_isbns=extracted_isbns,
                           ocr_error=ocr_error)

@bp.route('/add_comic', methods=['GET', 'POST'])
@login_required
def add_comic():
    form = AddComicForm()
    if form.validate_on_submit():
        comic = Comic(title=form.title.data, author=form.author.data, total_volumes=form.total_volumes.data)
        db.session.add(comic)
        db.session.commit()
        flash('Comic series added successfully!')
        return redirect(url_for('main.my_comics'))
    return render_template('add_comic.html', title='Add Comic', form=form)

@bp.route('/my_comics')
@login_required
def my_comics():
    # Display all comics in the system.
    # Users can add volumes to any of these comics.
    comics = Comic.query.order_by(Comic.title).all()
    return render_template('my_comics.html', title='Comic Series', comics=comics)

@bp.route('/comic/<int:comic_id>', methods=['GET', 'POST'])
@login_required
def comic_detail(comic_id):
    comic = Comic.query.get_or_404(comic_id)
    form = AddVolumeForm()
    if form.validate_on_submit():
        existing_volume = UserComicVolume.query.filter_by(
            owner=current_user, 
            comic_id=comic.id, # Ensure comic_id is used for filtering
            volume_number=form.volume_number.data
        ).first()
        if existing_volume:
            flash('You already have this volume in your collection for this series.')
        else:
            user_volume = UserComicVolume(
                owner=current_user, 
                comic_id=comic.id, # Ensure comic_id is used
                volume_number=form.volume_number.data, 
                purchase_date=form.purchase_date.data
            )
            db.session.add(user_volume)
            db.session.commit()
            flash(f'Volume {form.volume_number.data} added to {comic.title}!')
        return redirect(url_for('main.comic_detail', comic_id=comic_id))
    
    user_volumes = UserComicVolume.query.filter_by(owner=current_user, comic_id=comic.id).order_by(UserComicVolume.volume_number).all()
    return render_template('comic_detail.html', title=comic.title, comic=comic, user_volumes=user_volumes, form=form)

@bp.route('/register', methods=['GET', 'POST'])
def register():
    if current_user.is_authenticated:
        return redirect(url_for('main.index'))
    form = RegistrationForm()
    if form.validate_on_submit():
        user = User(username=form.username.data, email=form.email.data)
        user.set_password(form.password.data)
        db.session.add(user)
        db.session.commit()
        flash('Congratulations, you are now a registered user!')
        return redirect(url_for('main.login'))
    return render_template('register.html', title='Register', form=form)

@bp.route('/login', methods=['GET', 'POST'])
def login():
    if current_user.is_authenticated:
        return redirect(url_for('main.index'))
    form = LoginForm()
    if form.validate_on_submit():
        user = User.query.filter_by(username=form.username.data).first()
        if user is None or not user.check_password(form.password.data):
            flash('Invalid username or password')
            return redirect(url_for('main.login'))
        login_user(user, remember=form.remember_me.data)
        next_page = request.args.get('next')
        if not next_page or werkzeug.urls.url_parse(next_page).netloc != '': # Use attribute
            next_page = url_for('main.index')
        return redirect(next_page)
    return render_template('login.html', title='Sign In', form=form)

@bp.route('/logout')
def logout():
    logout_user()
    return redirect(url_for('main.index'))

# Helper function to parse URL for safe redirection
# import werkzeug.urls
