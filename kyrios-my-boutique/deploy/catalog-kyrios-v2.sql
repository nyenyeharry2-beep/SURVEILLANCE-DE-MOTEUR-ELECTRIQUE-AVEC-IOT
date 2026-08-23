-- ═══════════════════════════════════════════════════════════════
-- KYRIOS MY BOUTIQUE — Catalogue v2 (photos réelles utilisateur)
-- Exécuter dans phpMyAdmin APRÈS catalog-kyrios.sql
-- ═══════════════════════════════════════════════════════════════

SET @seller_id = (SELECT id FROM users WHERE email='boutique@kyrios.page.gd' LIMIT 1);
SET @seller_id = IFNULL(@seller_id, (SELECT id FROM users WHERE role='vendeur' LIMIT 1));

-- Mettre à jour mode homme (chemises boutique réelles)
UPDATE products SET
  title = 'Chemise Boutique — Marron Pois',
  description = 'Chemise marron à pois, poches poitrine, style premium. Boutique WOODCOCK.',
  image_url = '/uploads/products/chemise-marron.jpg'
WHERE image_url = '/uploads/products/veste-bordeaux.jpg';

UPDATE products SET
  title = 'Chemise Boutique — Bleu Floral',
  description = 'Chemise bleu motif floral abstrait, poches utilitaires. Collection Romeo/Elegance.',
  image_url = '/uploads/products/chemise-bleu-fleur.jpg'
WHERE image_url = '/uploads/products/veste-creme.jpg';

-- Nouveaux produits mode femme
INSERT INTO products (seller_id, title, description, price, category, stock, image_url) VALUES
(@seller_id, 'Ensemble Satin — Crème Brodé', 'Ensemble deux-pièces satin crème, broderie paon multicolore, pantalon fluide.', 145.00, 'mode', 6, '/uploads/products/ensemble-creme.jpg'),
(@seller_id, 'Robe Florale — Menthe Grand Palace', 'Robe longue menthe à fleurs, manches bouffantes, ceinture rose. Style gala luxe.', 229.00, 'mode', 4, '/uploads/products/robe-floral-menthe.jpg'),
(@seller_id, 'Robe Florale — Lavande Grand Palace', 'Robe lavande imprimé floral, épaules sequins vert, ceinture assortie.', 239.00, 'mode', 4, '/uploads/products/robe-floral-lavande.jpg'),
(@seller_id, 'Ensemble Brodé — Noir Paon', 'Ensemble noir satin, broderie paon multicolore, pantalon rayures argent.', 155.00, 'mode', 5, '/uploads/products/ensemble-noir.jpg'),
(@seller_id, 'Ensemble Satin — Bleu Nuit', 'Ensemble bleu satin, broderie florale, pantalon wide-leg rayé.', 149.00, 'mode', 5, '/uploads/products/ensemble-bleu-satin.jpg');

-- Nouveaux produits mode homme
INSERT INTO products (seller_id, title, description, price, category, stock, image_url) VALUES
(@seller_id, 'Chemise Boutique — Motif Tribal', 'Chemise marron motif tribal blanc, style graphique audacieux. Taille M-XL.', 89.00, 'mode-homme', 8, '/uploads/products/chemise-tribal.jpg'),
(@seller_id, 'Chemise Boutique — Étoiles Brodées', 'Chemise grise étoiles blanches, poches brodées dorées. Pièce unique.', 95.00, 'mode-homme', 6, '/uploads/products/chemise-etoile.jpg'),
(@seller_id, 'Chemise Utility — Kaki', 'Chemise kaki style utility, surpiqûres contrastées, poches poitrine.', 79.00, 'mode-homme', 10, '/uploads/products/chemise-kaki.jpg');

-- Publications fil d'actualité
INSERT INTO posts (user_id, content, product_id) VALUES
(@seller_id, '🌸 Nouveaux ensembles satin & robes florales Grand Palace — Élégance luxe pour vos soirées !',
 (SELECT id FROM products WHERE image_url='/uploads/products/robe-floral-menthe.jpg' LIMIT 1)),
(@seller_id, '👔 Collection chemises homme boutique — Motifs uniques, qualité premium. Stock limité !',
 (SELECT id FROM products WHERE image_url='/uploads/products/chemise-tribal.jpg' LIMIT 1));
