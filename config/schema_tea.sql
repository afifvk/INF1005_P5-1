-- ============================================================
-- Tea Filter Migration
-- Run this AFTER schema.sql to add tea-specific columns.
-- ============================================================

USE store_db;

-- Add tea-specific columns to products table
ALTER TABLE products
    ADD COLUMN flavour          VARCHAR(100) NOT NULL DEFAULT 'Earthy',
    ADD COLUMN health_benefits  VARCHAR(200) NOT NULL DEFAULT 'Antioxidants',
    ADD COLUMN caffeine_level   ENUM('None','Low','Medium','High') NOT NULL DEFAULT 'Medium',
    ADD COLUMN origin           VARCHAR(100) NOT NULL DEFAULT 'China';

-- Seed tea products
INSERT INTO products (name, description, flavour, health_benefits, caffeine_level, origin, price, image, stock, is_active) VALUES
('Dragon Well Green Tea',   'A classic Chinese green tea with a smooth, nutty flavour and a clean finish. One of China\'s most celebrated teas.',      'Nutty',   'Antioxidants, Weight Loss',            'Low',    'China',        12.99, 'placeholder.svg', 40, 1),
('Earl Grey Black Tea',     'A bold black tea infused with aromatic bergamot oil. A timeless British favourite with a floral citrus twist.',           'Floral',  'Energy Boost, Digestion',              'High',   'UK',           10.99, 'placeholder.svg', 60, 1),
('Peppermint Herbal Tea',   'A refreshing caffeine-free herbal tea with an intensely cool and minty character. Perfect before bed.',                   'Minty',   'Digestion, Sleep Aid',                 'None',   'Egypt',         8.99, 'placeholder.svg', 80, 1),
('Darjeeling First Flush',  'Harvested in early spring from the misty hills of Darjeeling. Light, floral and delicately muscatel.',                    'Floral',  'Antioxidants, Stress Relief',          'Medium', 'India',        18.99, 'placeholder.svg', 25, 1),
('Oolong Milk Tea',         'A creamy semi-oxidised oolong with a naturally buttery sweetness. Wonderful on its own or with a splash of milk.',        'Creamy',  'Metabolism Boost, Antioxidants',       'Medium', 'Taiwan',       15.99, 'placeholder.svg', 35, 1),
('Rooibos Vanilla',         'South African red bush tea with warm vanilla notes. Naturally caffeine-free and packed with minerals.',                   'Sweet',   'Antioxidants, Sleep Aid',              'None',   'South Africa',  9.99, 'placeholder.svg', 50, 1),
('Matcha Ceremonial Grade', 'Stone-ground Japanese matcha of the highest grade. Vivid green, umami-rich, and deeply energising.',                      'Earthy',  'Energy Boost, Focus, Antioxidants',    'High',   'Japan',        24.99, 'placeholder.svg', 20, 1),
('Chamomile Honey',         'Delicate chamomile blossoms with a hint of natural honey sweetness. A calming, golden-hued bedtime ritual.',              'Sweet',   'Sleep Aid, Stress Relief, Digestion',  'None',   'Germany',       7.99, 'placeholder.svg', 70, 1),
('Sencha Classic',          'Japan\'s most popular everyday green tea. Fresh, grassy and slightly sweet with a clean vegetal finish.',                 'Grassy',  'Antioxidants, Weight Loss, Focus',     'Low',    'Japan',        11.99, 'placeholder.svg', 55, 1);
