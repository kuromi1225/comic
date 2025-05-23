from flask_wtf import FlaskForm
from wtforms import StringField, PasswordField, BooleanField, SubmitField, IntegerField, DateField
from wtforms.validators import DataRequired, ValidationError, Email, EqualTo, Optional
from flask_wtf.file import FileField, FileAllowed # Added for file uploads
# Import your models here if needed for validation
# from app.models import User

# Example Form (can be expanded later)
class LoginForm(FlaskForm):
    username = StringField('Username', validators=[DataRequired()])
    password = PasswordField('Password', validators=[DataRequired()])
    remember_me = BooleanField('Remember Me')
    submit = SubmitField('Sign In')

class RegistrationForm(FlaskForm):
    username = StringField('Username', validators=[DataRequired()])
    email = StringField('Email', validators=[DataRequired(), Email()])
    password = PasswordField('Password', validators=[DataRequired()])
    password2 = PasswordField(
        'Repeat Password', validators=[DataRequired(), EqualTo('password')])
    submit = SubmitField('Register')

    def validate_username(self, username):
        from app.models import User # Import here to avoid circular dependency
        user = User.query.filter_by(username=username.data).first()
        if user is not None:
            raise ValidationError('Please use a different username.')

    def validate_email(self, email):
        from app.models import User # Import here to avoid circular dependency
        user = User.query.filter_by(email=email.data).first()
        if user is not None:
            raise ValidationError('Please use a different email address.')

class AddComicForm(FlaskForm):
    title = StringField('Title', validators=[DataRequired()])
    author = StringField('Author')
    total_volumes = IntegerField('Total Volumes', validators=[Optional()])
    submit = SubmitField('Add Comic')

class AddVolumeForm(FlaskForm):
    volume_number = IntegerField('Volume Number', validators=[DataRequired()])
    purchase_date = DateField('Purchase Date', validators=[Optional()])
    submit = SubmitField('Add Volume')

class UploadReceiptForm(FlaskForm):
    receipt_image = FileField('Upload Receipt Image', validators=[
        DataRequired(),
        FileAllowed(['jpg', 'jpeg', 'png'], 'Images only!')
    ])
    submit = SubmitField('Extract ISBNs from Receipt')
