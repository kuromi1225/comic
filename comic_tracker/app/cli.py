import click
from flask.cli import with_appcontext
from app import db # Corrected import
from app.models import ComicRelease # Corrected import
from datetime import date

@click.command(name='load-mock-releases')
@with_appcontext
def load_mock_releases_command():
    """Clears existing comic releases and loads new mock data."""
    try:
        # Clear existing data
        num_deleted = ComicRelease.query.delete()
        db.session.commit()
        if num_deleted > 0:
            click.echo(f'Deleted {num_deleted} existing comic release(s).')
        else:
            click.echo('No existing comic releases to delete.')

        mock_data = [
            {'comic_title_api': 'ワンダーアドベンチャー', 'volume_number_api': 10, 'release_date': date(2024, 7, 15), 'source_name': 'MockCalendar'},
            {'comic_title_api': 'マジカル学園記', 'volume_number_api': 3, 'release_date': date(2024, 7, 20), 'source_name': 'MockCalendar'},
            {'comic_title_api': 'ワンダーアドベンチャー', 'volume_number_api': 11, 'release_date': date(2024, 8, 15), 'source_name': 'MockCalendar'},
            {'comic_title_api': '宇宙戦記ゼロ', 'volume_number_api': 1, 'release_date': date(2024, 7, 5), 'source_name': 'MockCalendar'},
            {'comic_title_api': 'サラリーマン戦士Y', 'volume_number_api': 5, 'release_date': date(2024, 6, 28), 'source_name': 'MockCalendar'}, # Recent past
            {'comic_title_api': '異世界食堂へようこそ', 'volume_number_api': 2, 'release_date': date(2024, 9, 1), 'source_name': 'MockCalendar'}, # Future
        ]
        
        for item_data in mock_data:
            release = ComicRelease(
                comic_title_api=item_data['comic_title_api'],
                volume_number_api=item_data['volume_number_api'],
                release_date=item_data['release_date'],
                source_name=item_data['source_name']
            )
            db.session.add(release)
        
        db.session.commit()
        click.echo(f'Loaded {len(mock_data)} mock comic releases.')

    except Exception as e:
        db.session.rollback()
        click.echo(f'An error occurred: {e}')
        click.echo('Mock data loading failed. Database has been rolled back.')

def init_cli(app):
    app.cli.add_command(load_mock_releases_command)
