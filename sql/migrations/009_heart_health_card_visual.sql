-- Use the same branded image-card treatment for Heart Health as the other
-- homepage cards. Safe to re-run and scoped to the Heart Health card only.
UPDATE home_cards
SET image = 'assets/images/card-heart-health.svg'
WHERE link_path = 'heart-health'
  AND title_en = 'Heart Health';

UPDATE home_card_media media
INNER JOIN home_cards card ON card.id = media.card_id
SET media.visual_type = 'image',
    media.icon_key = 'heart',
    media.image_path = 'assets/images/card-heart-health.svg',
    media.video_path = NULL,
    media.poster_path = 'assets/images/card-heart-health.svg'
WHERE card.link_path = 'heart-health'
  AND card.title_en = 'Heart Health';

INSERT INTO home_card_media (card_id, visual_type, icon_key, image_path, poster_path)
SELECT card.id, 'image', 'heart', 'assets/images/card-heart-health.svg', 'assets/images/card-heart-health.svg'
FROM home_cards card
LEFT JOIN home_card_media media ON media.card_id = card.id
WHERE card.link_path = 'heart-health'
  AND card.title_en = 'Heart Health'
  AND media.card_id IS NULL;
