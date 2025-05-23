from app import db, login_manager
from flask_login import UserMixin
from werkzeug.security import generate_password_hash, check_password_hash
from datetime import datetime

@login_manager.user_loader
def load_user(user_id):
    return User.query.get(int(user_id))

class User(UserMixin, db.Model):
    id = db.Column(db.Integer, primary_key=True)
    username = db.Column(db.String(64), index=True, unique=True, nullable=False)
    email = db.Column(db.String(120), index=True, unique=True, nullable=False)
    password_hash = db.Column(db.String(128), nullable=False)

    def set_password(self, password):
        self.password_hash = generate_password_hash(password)

    def check_password(self, password):
        return check_password_hash(self.password_hash, password)

    password_hash = db.Column(db.String(128), nullable=False)
    volumes = db.relationship('UserComicVolume', backref='owner', lazy='dynamic') # Added relationship

    def set_password(self, password):
        self.password_hash = generate_password_hash(password)

    def check_password(self, password):
        return check_password_hash(self.password_hash, password)

    def __repr__(self):
        return f'<User {self.username}>'

class Comic(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    title = db.Column(db.String(140), nullable=False)
    author = db.Column(db.String(140))
    total_volumes = db.Column(db.Integer)
    user_volumes = db.relationship('UserComicVolume', backref='comic', lazy='dynamic')

    def __repr__(self):
        return f'<Comic {self.title}>'

class UserComicVolume(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey('user.id'), nullable=False)
    comic_id = db.Column(db.Integer, db.ForeignKey('comic.id'), nullable=False)
    volume_number = db.Column(db.Integer, nullable=False)
    purchase_date = db.Column(db.Date)
    
    __table_args__ = (db.UniqueConstraint('user_id', 'comic_id', 'volume_number', name='_user_comic_volume_uc'),)

    def __repr__(self):
        return f'<UserComicVolume User {self.user_id} Comic {self.comic_id} Volume {self.volume_number}>'

class ComicRelease(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    comic_title_api = db.Column(db.String(255), nullable=False)
    volume_number_api = db.Column(db.Integer, nullable=False)
    release_date = db.Column(db.Date, nullable=False)
    source_name = db.Column(db.String(100)) # e.g., 'ComicCalendarX'

    def __repr__(self):
        return f'<ComicRelease {self.comic_title_api} Vol. {self.volume_number_api} on {self.release_date}>'
