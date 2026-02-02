CREATE TABLE roles (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL
);

INSERT INTO roles (name) VALUES
('admin'),
('user');

CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    profile_picture VARCHAR(255),
    role INTEGER REFERENCES roles(id) DEFAULT 2
);

INSERT INTO users (username, email, password) VALUES
('testuser', 'test@test.pl', '$2y$10$Bq3M0CXba81qm7jhr1kVgOqee09RpSZY4zcSBdkjiYJvkS5STrerO')
ON CONFLICT (email) DO NOTHING;

INSERT INTO users (username, email, password, role) VALUES
('admin', 'admin@test.pl', '$2y$10$Bq3M0CXba81qm7jhr1kVgOqee09RpSZY4zcSBdkjiYJvkS5STrerO', 1)
ON CONFLICT (email) DO NOTHING;

CREATE TABLE countries (
    name VARCHAR(100) PRIMARY KEY
);

CREATE TABLE reels (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id),
    country VARCHAR(100) REFERENCES countries(name),
    video_name VARCHAR(255) NOT NULL,
    thumbnail_name VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE country_stats (
    id SERIAL PRIMARY KEY,
    country_name VARCHAR(100) REFERENCES countries(name) ON DELETE CASCADE UNIQUE, 
    total_reels INTEGER DEFAULT 0,
    percentage_share DECIMAL(5,2) DEFAULT 0.00,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE OR REPLACE FUNCTION update_country_stats_function() 
RETURNS TRIGGER AS $$
DECLARE
    videos_count INTEGER;
BEGIN
    SELECT COUNT(*) INTO videos_count 
    FROM reels 
    WHERE country = NEW.country;

    INSERT INTO country_stats (country_name, total_reels, last_updated)
    VALUES (NEW.country, videos_count, CURRENT_TIMESTAMP)
    ON CONFLICT (country_name) 
    DO UPDATE SET 
        total_reels = EXCLUDED.total_reels,
        last_updated = CURRENT_TIMESTAMP;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql; 

CREATE TRIGGER trigger_update_reels_count
AFTER INSERT ON reels
FOR EACH ROW
EXECUTE FUNCTION update_country_stats_function();

CREATE VIEW v_country_percentages AS
SELECT 
    cs.country_name,
    cs.total_reels,
    ROUND((cs.total_reels::DECIMAL / NULLIF((SELECT COUNT(*) FROM reels), 0)::DECIMAL) * 100, 2) as percentage_share
FROM country_stats cs;

-- Europa
INSERT INTO countries (name) VALUES
('Albania'), ('Andorra'), ('Austria'), ('Belarus'),
('Belgium'), ('Bosnia and Herzegovina'), ('Bulgaria'), ('Croatia'),
('Cyprus'), ('Czech Republic'), ('Denmark'), ('Estonia'),
('Finland'), ('France'), ('Germany'), ('Greece'),
('Hungary'), ('Iceland'), ('Ireland'), ('Italy'),
('Kosovo'), ('Latvia'), ('Liechtenstein'), ('Lithuania'),
('Luxembourg'), ('Malta'), ('Moldova'), ('Monaco'),
('Montenegro'), ('Netherlands'), ('North Macedonia'), ('Norway'),
('Poland'), ('Portugal'), ('Romania'), ('Russia'),
('San Marino'), ('Serbia'), ('Slovakia'), ('Slovenia'),
('Spain'), ('Sweden'), ('Switzerland'), ('Turkey'),
('Ukraine'), ('United Kingdom'), ('Vatican City')
ON CONFLICT (name) DO NOTHING;

-- Azja
INSERT INTO countries (name) VALUES
('Afghanistan'), ('Armenia'), ('Azerbaijan'), ('Bahrain'),
('Bangladesh'), ('Bhutan'), ('Brunei'), ('Cambodia'),
('China'), ('Georgia'), ('India'),
('Indonesia'), ('Iran'), ('Iraq'), ('Israel'),
('Japan'), ('Jordan'), ('Kazakhstan'), ('Kuwait'),
('Kyrgyzstan'), ('Laos'), ('Lebanon'),
('Malaysia'), ('Maldives'), ('Mongolia'), ('Myanmar'),
('Nepal'), ('North Korea'), ('Oman'), ('Pakistan'),
('Palestine'), ('Philippines'), ('Qatar'), ('Saudi Arabia'),
('Singapore'), ('South Korea'), ('Sri Lanka'), ('Syria'),
('Taiwan'), ('Tajikistan'), ('Thailand'), ('Timor-Leste'),
('Turkmenistan'), ('United Arab Emirates'), ('Uzbekistan'), ('Vietnam'),
('Yemen')
ON CONFLICT (name) DO NOTHING;

-- Ameryka Północna i Środkowa
INSERT INTO countries (name) VALUES
('Antigua and Barbuda'), ('Bahamas'), ('Barbados'), ('Belize'),
('Canada'), ('Costa Rica'), ('Cuba'), ('Dominica'),
('Dominican Republic'), ('El Salvador'), ('Grenada'), ('Guatemala'),
('Haiti'), ('Honduras'), ('Jamaica'), ('Mexico'),
('Nicaragua'), ('Panama'), ('Saint Kitts and Nevis'), ('Saint Lucia'),
('Saint Vincent and the Grenadines'), ('Trinidad and Tobago'), ('United States')
ON CONFLICT (name) DO NOTHING;

-- Ameryka Południowa
INSERT INTO countries (name) VALUES
('Argentina'), ('Bolivia'), ('Brazil'), ('Chile'),
('Colombia'), ('Ecuador'), ('Guyana'), ('Paraguay'),
('Peru'), ('Suriname'), ('Uruguay'), ('Venezuela')
ON CONFLICT (name) DO NOTHING;

-- Afryka
INSERT INTO countries (name) VALUES
('Algeria'), ('Angola'), ('Benin'), ('Botswana'),
('Burkina Faso'), ('Burundi'), ('Cabo Verde'), ('Cameroon'),
('Central African Republic'), ('Chad'), ('Comoros'), ('Democratic Republic of the Congo'),
('Republic of Congo'), ('Cote d''Ivoire'), ('Djibouti'), ('Egypt'),
('Equatorial Guinea'), ('Eritrea'), ('Eswatini'), ('Ethiopia'),
('Gabon'), ('Gambia'), ('Ghana'), ('Guinea'),
('Guinea-Bissau'), ('Kenya'), ('Lesotho'), ('Liberia'),
('Libya'), ('Madagascar'), ('Malawi'), ('Mali'),
('Mauritania'), ('Mauritius'), ('Morocco'), ('Mozambique'),
('Namibia'), ('Niger'), ('Nigeria'), ('Rwanda'),
('Sao Tome and Principe'), ('Senegal'), ('Seychelles'), ('Sierra Leone'),
('Somalia'), ('South Africa'), ('South Sudan'), ('Sudan'),
('Tanzania'), ('Togo'), ('Tunisia'), ('Uganda'),
('Zambia'), ('Zimbabwe')
ON CONFLICT (name) DO NOTHING;

-- Oceania
INSERT INTO countries (name) VALUES
('Australia'), ('Fiji'), ('Kiribati'), ('Marshall Islands'),
('Micronesia'), ('Nauru'), ('New Zealand'), ('Palau'),
('Papua New Guinea'), ('Samoa'), ('Solomon Islands'), ('Tonga'),
('Tuvalu'), ('Vanuatu')
ON CONFLICT (name) DO NOTHING;

-- Terytoria i wyspy
INSERT INTO countries (name) VALUES 
('Guadeloupe'),
('Martinique'),
('French Guiana'),
('Reunion'),
('Mayotte'),
('French Southern Territories'),

('Canary Islands'),
('Hawaii'),

('Aruba'),
('Curaçao'),
('Anguilla'),
('Bermuda'),
('Turks and Caicos Islands'),
('Greenland'),
('Western Sahara'),
('Faroe Islands'),
('Guam'),
('Saint-Martin'),
('Northern Mariana Islands'),
('Falkland Islands'),
('Cayman Islands'),
('New Caledonia'),
('Puerto Rico'),
('French Polynesia'),
('Sint Maarten')
ON CONFLICT (name) DO NOTHING;


INSERT INTO reels (user_id, country, video_name, thumbnail_name) VALUES
(1, 'Poland', 'media/testuser/reel_1768566523.mp4', 'media/testuser/thumb_reel_1768566523.jpg'),
(1, 'Germany', 'media/testuser/reel_1768568680.mp4', 'media/testuser/thumb_reel_1768568680.jpg'),
(1, 'France', 'media/testuser/reel_1768568757.mp4', 'media/testuser/thumb_reel_1768568757.jpg'),
(1, 'France', 'media/testuser/reel_1769636090.mp4', 'media/testuser/thumb_reel_1769636090.jpg'),
(1, 'France', 'media/testuser/reel_1769726981.mp4', 'media/testuser/thumb_reel_1769726981.jpg');

