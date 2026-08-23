-- ═══════════════════════════════════════════════════════════════
-- KYRIOS MY BOUTIQUE — Catalogue produits complet
-- Exécuter dans phpMyAdmin APRÈS update-v2.sql
-- ═══════════════════════════════════════════════════════════════

-- Boutique vendeur principal
INSERT INTO users (email, password_hash, full_name, role, shop_name, shop_description, bio, is_verified, phone) VALUES
('boutique@kyrios.page.gd', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'Kyrios Fashion', 'vendeur', 'Kyrios My Boutique', 
 'Mode femme, homme & enfant — Élégance et qualité',
 'Votre destination mode premium', 1, '+225 07 00 00 00 00')
ON DUPLICATE KEY UPDATE shop_name='Kyrios My Boutique', is_verified=1;

SET @seller_id = (SELECT id FROM users WHERE email='boutique@kyrios.page.gd' LIMIT 1);
SET @seller_id = IFNULL(@seller_id, (SELECT id FROM users WHERE role='vendeur' LIMIT 1));

-- ── MODE ENFANT — Little Princess (3-12 ans) ──
INSERT INTO products (seller_id, title, description, price, category, stock, image_url) VALUES
(@seller_id, 'Robe Little Princess — Satin Crème', 'Robe de soirée satin crème avec nœud structural, ages 3-12 ans. Collection Little Princess.', 89.00, 'mode-enfant', 15, '/uploads/products/lp-robe-creme.jpg'),
(@seller_id, 'Robe Little Princess — Bleu Ciel', 'Robe bleu ciel avec rose satinée à la taille, jupe plissée. Ages 3-12 ans.', 79.00, 'mode-enfant', 12, '/uploads/products/lp-robe-bleu.jpg'),
(@seller_id, 'Robe Little Princess — Rose Poudré', 'Robe rose poudré avec cristaux et fleur épaule. Fashion Show Little Princess.', 85.00, 'mode-enfant', 10, '/uploads/products/lp-robe-rose.jpg'),
(@seller_id, 'Robe Little Princess — Bleu Plissé', 'Robe bleu poudre plissée avec rose perles, collants dentelle. Ages 3-12 ans.', 75.00, 'mode-enfant', 14, '/uploads/products/lp-robe-bleu-plisse.jpg'),
(@seller_id, 'Robe Little Princess — Velours Rouge', 'Haut velours rouge, jupe blanche rosettes, tiare incluse. Ages 3-12 ans.', 95.00, 'mode-enfant', 8, '/uploads/products/lp-robe-rouge.jpg'),
(@seller_id, 'Robe Little Princess — Velours Noir', 'Haut velours noir, jupe crème volants, style gala. Ages 3-12 ans.', 95.00, 'mode-enfant', 8, '/uploads/products/lp-robe-noir.jpg');

-- ── MODE FEMME — Robes de soirée ──
INSERT INTO products (seller_id, title, description, price, category, stock, image_url) VALUES
(@seller_id, 'Robe de Soirée — Magenta Peplum', 'Robe longue magenta deux-pièces, manches bouffantes, dentelle 3D. Taille S-XL.', 199.00, 'mode', 6, '/uploads/products/robe-magenta.jpg'),
(@seller_id, 'Robe de Soirée — Bleu Argent', 'Robe sirène bleu poudre et argent, sequins floraux, manches longues.', 189.00, 'mode', 5, '/uploads/products/robe-bleu-argent.jpg'),
(@seller_id, 'Robe de Soirée — Bleu Marine', 'Robe navy peplum, perles et sequins, style sirène. Soirée & gala.', 219.00, 'mode', 4, '/uploads/products/robe-marine.jpg'),
(@seller_id, 'Ensemble Brodé — Bleu Marine', 'Ensemble deux-pièces broderie florale ton sur ton, pantalon wide-leg.', 149.00, 'mode', 8, '/uploads/products/ensemble-marine.jpg'),
(@seller_id, 'Ensemble Satin — Vert Olive', 'Ensemble satin vert olive, broderie perles dorées, pantalon fluide.', 139.00, 'mode', 7, '/uploads/products/ensemble-olive.jpg');

-- ── MODE HOMME — PASUXI ──
INSERT INTO products (seller_id, title, description, price, category, stock, image_url) VALUES
(@seller_id, 'Veste PASUXI — Bordeau', 'Veste bordeaux texture laine, col chemise, poches poitrine. Style urbain premium.', 129.00, 'mode-homme', 10, '/uploads/products/veste-bordeaux.jpg'),
(@seller_id, 'Veste PASUXI — Crème Shacket', 'Veste trucker crème oversize, boutons noirs, col classique. Tendance 2026.', 119.00, 'mode-homme', 12, '/uploads/products/veste-creme.jpg');

-- ── CHAUSSURES — Sneakers Premium ──
INSERT INTO products (seller_id, title, description, price, category, stock, image_url) VALUES
(@seller_id, 'Sneakers Platform — Blanc Or Noir', 'Baskets plateforme mesh blanc, bandes dorées, semelle épaisse. Pointures 36-42.', 89.00, 'chaussures', 20, '/uploads/products/sneakers-or-noir.jpg'),
(@seller_id, 'Sneakers Platform — Blanc Or Rose', 'Baskets plateforme blanc et rose poudré, logo doré, style luxe.', 85.00, 'chaussures', 18, '/uploads/products/sneakers-or-rose.jpg'),
(@seller_id, 'Sneakers Platform — Blanc Or Marron', 'Baskets plateforme monogramme, accents marron et or, confort premium.', 85.00, 'chaussures', 16, '/uploads/products/sneakers-or-marron.jpg');

-- Publications fil d'actualité
INSERT INTO posts (user_id, content, product_id) VALUES
(@seller_id, '👑 Nouvelle collection Little Princess ! Robes de gala pour petites princesses 3-12 ans. Découvrez nos modèles satin, velours et tulle.', (SELECT id FROM products WHERE image_url='/uploads/products/lp-robe-creme.jpg' LIMIT 1)),
(@seller_id, '✨ Robes de soirée femme — Élégance garantie pour vos galas et mariages. Nouveautés disponibles !', (SELECT id FROM products WHERE image_url='/uploads/products/robe-magenta.jpg' LIMIT 1)),
(@seller_id, '👟 Sneakers platform tendance — Confort et style luxe. Livraison rapide !', (SELECT id FROM products WHERE image_url='/uploads/products/sneakers-or-noir.jpg' LIMIT 1)),
(@seller_id, '🧥 Collection PASUXI homme — Vestes premium bordeaux et crème. Stock limité !', (SELECT id FROM products WHERE image_url='/uploads/products/veste-bordeaux.jpg' LIMIT 1));
