from app import db
from app.models import Comic, ComicRelease, UserComicVolume
from sqlalchemy import and_
from datetime import datetime

def get_new_releases_for_user(user, month, year):
    """
    Identifies comic releases for a given month and year that the user
    does not already own, based on matching titles in the Comic table.
    """
    # Step 1: Fetch ComicRelease entries for the given month and year.
    # This requires constructing date objects for the first and last day of the month.
    first_day_of_month = datetime(year, month, 1).date()
    if month == 12:
        last_day_of_month = datetime(year + 1, 1, 1).date()
        # SQLAlchemy filter is exclusive for the end of range, so we need to go to the next day
        # For simplicity, we'll query up to the first day of the next month.
        # A more precise way would be to get the actual last day of the month.
        next_month_first_day = datetime(year + 1, 1, 1).date()
    else:
        last_day_of_month = datetime(year, month + 1, 1).date() # day before next month starts
        next_month_first_day = datetime(year, month + 1, 1).date()

    relevant_releases = ComicRelease.query.filter(
        ComicRelease.release_date >= first_day_of_month,
        ComicRelease.release_date < next_month_first_day # Use < to exclude next month's first day
    ).all()

    new_releases_for_user = []

    for release in relevant_releases:
        # Step 2a: Try to find a Comic in the main comics table.
        comic_in_db = Comic.query.filter(Comic.title == release.comic_title_api).first()

        if comic_in_db:
            # Step 2b: Check if the current_user has a UserComicVolume entry for this Comic.id and volume_number.
            owned_volume = UserComicVolume.query.filter(
                UserComicVolume.user_id == user.id,
                UserComicVolume.comic_id == comic_in_db.id,
                UserComicVolume.volume_number == release.volume_number_api
            ).first()

            # Step 2c: If the Comic exists AND the user does NOT have that volume, it's a "new release for the user".
            if not owned_volume:
                new_releases_for_user.append({
                    'comic_id': comic_in_db.id, # For linking to comic_detail
                    'title': release.comic_title_api,
                    'volume_number': release.volume_number_api,
                    'release_date': release.release_date.strftime('%Y-%m-%d'),
                    'source_name': release.source_name
                })
                
    return new_releases_for_user

def get_comics_with_missing_volumes(user):
    """
    Identifies comic series for which the user owns at least one volume
    but has missing/skipped volumes in their collection, or is incomplete
    if total_volumes is known.
    """
    comics_with_status = []

    # Get all distinct comic_ids the user has volumes for
    owned_comic_ids_query = db.session.query(UserComicVolume.comic_id).filter_by(user_id=user.id).distinct()
    
    for (comic_id,) in owned_comic_ids_query: # Unpack the tuple
        comic = Comic.query.get(comic_id)
        if not comic:
            continue

        # Fetch all owned volume numbers for this series, sorted
        owned_volumes_for_series = UserComicVolume.query.filter_by(
            user_id=user.id, 
            comic_id=comic.id
        ).with_entities(UserComicVolume.volume_number).order_by(UserComicVolume.volume_number).all()
        
        owned_volume_numbers = [v[0] for v in owned_volumes_for_series]

        if not owned_volume_numbers:
            continue # Should not happen if comic_id came from UserComicVolume query

        missing_description_parts = []
        
        # Method 1: Check for gaps between min and max owned volumes
        min_owned = owned_volume_numbers[0]
        max_owned = owned_volume_numbers[-1]
        
        if max_owned > min_owned: # Only check for gaps if there's a range
            expected_volumes_in_range = set(range(min_owned, max_owned + 1))
            actual_owned_set = set(owned_volume_numbers)
            gaps_in_sequence = sorted(list(expected_volumes_in_range - actual_owned_set))

            if gaps_in_sequence:
                gap_strs = []
                i = 0
                while i < len(gaps_in_sequence):
                    start_gap = gaps_in_sequence[i]
                    j = i
                    while j + 1 < len(gaps_in_sequence) and gaps_in_sequence[j+1] == gaps_in_sequence[j] + 1:
                        j += 1
                    end_gap = gaps_in_sequence[j]
                    if start_gap == end_gap:
                        gap_strs.append(f"Vol. {start_gap}")
                    else:
                        gap_strs.append(f"Vol. {start_gap}-{end_gap}")
                    i = j + 1
                missing_description_parts.append(f"Gaps in owned range: {', '.join(gap_strs)}")

        # Method 2: Check for missing volumes if total_volumes is known and user owns up to max_owned < total_volumes
        if comic.total_volumes and max_owned < comic.total_volumes:
            # Only suggest later volumes if there are no gaps up to max_owned
            all_up_to_max_present = True
            for vol_num in range(1, max_owned + 1): # Assuming volumes start at 1
                if vol_num not in owned_volume_numbers:
                    all_up_to_max_present = False
                    break
            
            if all_up_to_max_present:
                 missing_later_start = max_owned + 1
                 if missing_later_start <= comic.total_volumes:
                    if missing_later_start == comic.total_volumes:
                        missing_description_parts.append(f"Missing later volumes: Vol. {missing_later_start}")
                    else:
                        missing_description_parts.append(f"Missing later volumes: Vol. {missing_later_start}-{comic.total_volumes}")

        if missing_description_parts:
            comics_with_status.append({
                'comic_id': comic.id,
                'title': comic.title,
                'author': comic.author,
                'owned_summary': f"Owned {len(owned_volume_numbers)} volumes (Min: {min_owned}, Max: {max_owned})",
                'missing_info': "; ".join(missing_description_parts)
            })
            
    return comics_with_status
