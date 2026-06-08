-- ═══════════════════════════════════════════════════════════
-- POS Pro v1.3 — Base de données complète pour Supabase
-- © 2026 Oscar Mulume Izuba — M-SEC Technology Conseil
-- ═══════════════════════════════════════════════════════════
-- Copiez-colcez ce code dans Supabase → SQL Editor → Run

-- ═══════════════════════════════════════════════════════════
-- 1. TABLE RESTAURANTS
-- ═══════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS restaurants (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    address TEXT,
    phone VARCHAR(20),
    email VARCHAR(100),
    logo_path VARCHAR(255),
    photo_path VARCHAR(255),
    currency VARCHAR(5) DEFAULT 'FC',
    tax_rate DECIMAL(5,2) DEFAULT 0,
    receipt_header VARCHAR(255),
    receipt_footer VARCHAR(255),
    is_active BOOLEAN DEFAULT true,
    status VARCHAR(20) DEFAULT 'active',
    type VARCHAR(20) DEFAULT 'permanent',
    has_electronic_drawer BOOLEAN DEFAULT true,
    sla_warning_minutes INTEGER DEFAULT 30,
    default_currency VARCHAR(5) DEFAULT 'FC',
    secondary_currency VARCHAR(5) DEFAULT 'USD',
    exchange_rate DECIMAL(12,2) DEFAULT 2850,
    subscription_ends_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- ═══════════════════════════════════════════════════════════
-- 2. TABLE USERS
-- ═══════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS users (
    id BIGSERIAL PRIMARY KEY,
    restaurant_id BIGINT REFERENCES restaurants(id) ON DELETE CASCADE,
    pos_terminal_id BIGINT,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    pin_code VARCHAR(255),
    webauthn_id VARCHAR(255),
    webauthn_public_key TEXT,
    webauthn_name VARCHAR(100),
    role VARCHAR(20) NOT NULL DEFAULT 'cashier',
    is_active BOOLEAN DEFAULT true,
    last_login_at TIMESTAMP,
    avatar_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- ═══════════════════════════════════════════════════════════
-- 3. TABLE POS_TERMINALS
-- ═══════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS pos_terminals (
    id BIGSERIAL PRIMARY KEY,
    restaurant_id BIGINT NOT NULL REFERENCES restaurants(id) ON DELETE CASCADE,
    name VARCHAR(50) NOT NULL,
    device_id VARCHAR(100) UNIQUE,
    ip_address VARCHAR(45),
    is_active BOOLEAN DEFAULT true,
    last_seen_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- ═══════════════════════════════════════════════════════════
-- 4. TABLE RESTAURANT_TABLES
-- ═══════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS restaurant_tables (
    id BIGSERIAL PRIMARY KEY,
    restaurant_id BIGINT NOT NULL REFERENCES restaurants(id) ON DELETE CASCADE,
    pos_terminal_id BIGINT REFERENCES pos_terminals(id) ON DELETE SET NULL,
    name VARCHAR(50) NOT NULL,
    status VARCHAR(20) DEFAULT 'available',
    capacity INTEGER,
    zone VARCHAR(30),
    is_active BOOLEAN DEFAULT true,
    current_order_id BIGINT,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- ═══════════════════════════════════════════════════════════
-- 5. TABLE CATEGORIES
-- ═══════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS categories (
    id BIGSERIAL PRIMARY KEY,
    restaurant_id BIGINT NOT NULL REFERENCES restaurants(id) ON DELETE CASCADE,
    name VARCHAR(50) NOT NULL,
    icon VARCHAR(10),
    color VARCHAR(7),
    display_order INTEGER DEFAULT 0,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- ═══════════════════════════════════════════════════════════
-- 6. TABLE PRODUCTS
-- ═══════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS products (
    id BIGSERIAL PRIMARY KEY,
    restaurant_id BIGINT NOT NULL REFERENCES restaurants(id) ON DELETE CASCADE,
    category_id BIGINT REFERENCES categories(id) ON DELETE SET NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL DEFAULT 0,
    cost_price DECIMAL(10,2) DEFAULT 0,
    cost_price_calculated DECIMAL(10,2) DEFAULT 0,
    food_cost_percentage DECIMAL(5,2) DEFAULT 0,
    margin_percentage DECIMAL(5,2) DEFAULT 0,
    image_path VARCHAR(255),
    sort_order INTEGER DEFAULT 0,
    prep_time_minutes INTEGER DEFAULT 15,
    kitchen_route VARCHAR(20) DEFAULT 'kitchen',
    is_available BOOLEAN DEFAULT true,
    stock_quantity DECIMAL(12,3) DEFAULT 0,
    low_stock_threshold DECIMAL(12,3) DEFAULT 0,
    stock_alert_threshold DECIMAL(12,3) DEFAULT 0,
    stock_status VARCHAR(20) DEFAULT 'normal',
    track_inventory BOOLEAN DEFAULT false,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- ═══════════════════════════════════════════════════════════
-- 7. TABLE ORDERS
-- ═══════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS orders (
    id BIGSERIAL PRIMARY KEY,
    restaurant_id BIGINT NOT NULL REFERENCES restaurants(id) ON DELETE CASCADE,
    pos_terminal_id BIGINT REFERENCES pos_terminals(id) ON DELETE SET NULL,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    table_id BIGINT REFERENCES restaurant_tables(id) ON DELETE SET NULL,
    order_number VARCHAR(20) UNIQUE NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    tax_amount DECIMAL(10,2) DEFAULT 0,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    payment_method VARCHAR(20) DEFAULT 'cash',
    payment_reference VARCHAR(100),
    cash_received DECIMAL(10,2),
    change_given DECIMAL(10,2),
    customer_name VARCHAR(100),
    customer_phone VARCHAR(20),
    status VARCHAR(20) DEFAULT 'pending',
    kitchen_status VARCHAR(20),
    sent_to_kitchen_at TIMESTAMP,
    ready_at TIMESTAMP,
    delivered_at TIMESTAMP,
    cancelled_by BIGINT REFERENCES users(id) ON DELETE SET NULL,
    cancellation_reason TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Ajouter la FK pour current_order_id (après création de orders)
ALTER TABLE restaurant_tables 
    ADD CONSTRAINT fk_current_order 
    FOREIGN KEY (current_order_id) REFERENCES orders(id) ON DELETE SET NULL;

-- ═══════════════════════════════════════════════════════════
-- 8. TABLE ORDER_ITEMS
-- ═══════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS order_items (
    id BIGSERIAL PRIMARY KEY,
    order_id BIGINT NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    product_id BIGINT NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    product_name VARCHAR(100) NOT NULL,
    quantity INTEGER NOT NULL DEFAULT 1,
    price_at_sale DECIMAL(10,2) NOT NULL DEFAULT 0,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
    notes TEXT,
    kitchen_status VARCHAR(20) DEFAULT 'en_attente',
    kitchen_route VARCHAR(20) DEFAULT 'kitchen',
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- ═══════════════════════════════════════════════════════════
-- 9. TABLE INGREDIENTS (Matières Premières)
-- ═══════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS ingredients (
    id BIGSERIAL PRIMARY KEY,
    restaurant_id BIGINT NOT NULL REFERENCES restaurants(id) ON DELETE CASCADE,
    name VARCHAR(100) NOT NULL,
    unit_of_measure VARCHAR(20) NOT NULL,
    cost_per_unit DECIMAL(10,4) NOT NULL DEFAULT 0,
    stock_quantity DECIMAL(12,3) DEFAULT 0,
    alert_threshold DECIMAL(12,3) DEFAULT 0,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- ═══════════════════════════════════════════════════════════
-- 10. TABLE PRODUCT_INGREDIENTS (Fiches Techniques / BOM)
-- ═══════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS product_ingredients (
    id BIGSERIAL PRIMARY KEY,
    product_id BIGINT NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    ingredient_id BIGINT NOT NULL REFERENCES ingredients(id) ON DELETE CASCADE,
    quantity_required DECIMAL(10,3) NOT NULL DEFAULT 0,
    unit_of_measure VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    UNIQUE(product_id, ingredient_id)
);

-- ═══════════════════════════════════════════════════════════
-- 11. TABLE WASTE_LOGS (Pertes/Démarque)
-- ═══════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS waste_logs (
    id BIGSERIAL PRIMARY KEY,
    restaurant_id BIGINT NOT NULL REFERENCES restaurants(id) ON DELETE CASCADE,
    item_type VARCHAR(20) NOT NULL,
    item_id BIGINT NOT NULL,
    item_name VARCHAR(100) NOT NULL,
    quantity DECIMAL(12,3) NOT NULL,
    unit_of_measure VARCHAR(20),
    cost_at_loss DECIMAL(10,2) DEFAULT 0,
    reason VARCHAR(50) NOT NULL,
    notes TEXT,
    reported_by BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- ═══════════════════════════════════════════════════════════
-- 12. TABLE CASH_SHIFTS
-- ═══════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS cash_shifts (
    id BIGSERIAL PRIMARY KEY,
    restaurant_id BIGINT NOT NULL REFERENCES restaurants(id) ON DELETE CASCADE,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    opened_at TIMESTAMP NOT NULL,
    closed_at TIMESTAMP,
    opening_balance_fc DECIMAL(12,2) DEFAULT 0,
    opening_balance_usd DECIMAL(12,2) DEFAULT 0,
    closing_count_fc DECIMAL(12,2),
    closing_count_usd DECIMAL(12,2),
    expected_fc DECIMAL(12,2),
    expected_usd DECIMAL(12,2),
    gap_fc DECIMAL(12,2),
    gap_usd DECIMAL(12,2),
    total_sales_fc DECIMAL(12,2) DEFAULT 0,
    total_sales_usd DECIMAL(12,2) DEFAULT 0,
    total_refunds_fc DECIMAL(12,2) DEFAULT 0,
    total_orders INTEGER DEFAULT 0,
    total_cancelled INTEGER DEFAULT 0,
    status VARCHAR(20) DEFAULT 'open',
    audited_by BIGINT REFERENCES users(id) ON DELETE SET NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- ═══════════════════════════════════════════════════════════
-- 13. TABLE ORDER_PAYMENTS (Paiements fractionnés)
-- ═══════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS order_payments (
    id BIGSERIAL PRIMARY KEY,
    order_id BIGINT NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    restaurant_id BIGINT NOT NULL REFERENCES restaurants(id) ON DELETE CASCADE,
    payment_method VARCHAR(20) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    currency VARCHAR(5) DEFAULT 'FC',
    exchange_rate VARCHAR(20) DEFAULT '1',
    payment_reference VARCHAR(100),
    processed_by BIGINT REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- ═══════════════════════════════════════════════════════════
-- 14. TABLE AUDIT_LOGS
-- ═══════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT REFERENCES users(id) ON DELETE SET NULL,
    action VARCHAR(50) NOT NULL,
    entity_type VARCHAR(50),
    entity_id BIGINT,
    old_values JSONB,
    new_values JSONB,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT NOW()
);

-- ═══════════════════════════════════════════════════════════
-- 15. TABLE SITE_SETTINGS
-- ═══════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS site_settings (
    id BIGSERIAL PRIMARY KEY,
    key VARCHAR(100) UNIQUE NOT NULL,
    value TEXT,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- ═══════════════════════════════════════════════════════════
-- 16. TABLE LICENSES
-- ═══════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS licenses (
    id BIGSERIAL PRIMARY KEY,
    restaurant_id BIGINT NOT NULL REFERENCES restaurants(id) ON DELETE CASCADE,
    license_key VARCHAR(100) UNIQUE NOT NULL,
    plan VARCHAR(20) DEFAULT 'standard',
    is_active BOOLEAN DEFAULT true,
    expires_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- ═══════════════════════════════════════════════════════════
-- 17. TABLE PASSWORD_RESETS
-- ═══════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS password_resets (
    email VARCHAR(100) PRIMARY KEY,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT NOW()
);

-- ═══════════════════════════════════════════════════════════
-- 18. TABLE SESSIONS
-- ═══════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(255) PRIMARY KEY,
    user_id BIGINT REFERENCES users(id) ON DELETE CASCADE,
    ip_address VARCHAR(45),
    user_agent TEXT,
    payload TEXT NOT NULL,
    last_activity INTEGER NOT NULL
);

-- ═══════════════════════════════════════════════════════════
-- INDEX POUR PERFORMANCE
-- ═══════════════════════════════════════════════════════════
CREATE INDEX IF NOT EXISTS idx_orders_restaurant_created ON orders(restaurant_id, created_at);
CREATE INDEX IF NOT EXISTS idx_orders_status ON orders(status);
CREATE INDEX IF NOT EXISTS idx_order_items_order_id ON order_items(order_id);
CREATE INDEX IF NOT EXISTS idx_products_restaurant ON products(restaurant_id);
CREATE INDEX IF NOT EXISTS idx_categories_restaurant ON categories(restaurant_id);
CREATE INDEX IF NOT EXISTS idx_users_restaurant ON users(restaurant_id);
CREATE INDEX NOT EXISTS idx_audit_logs_user ON audit_logs(user_id);
CREATE INDEX IF NOT EXISTS idx_waste_logs_restaurant ON waste_logs(restaurant_id, created_at);
CREATE INDEX IF NOT EXISTS idx_cash_shifts_restaurant ON cash_shifts(restaurant_id, status);

-- ═══════════════════════════════════════════════════════════
-- DONNÉES DE DÉMO
-- ═══════════════════════════════════════════════════════════

-- Restaurant démo
INSERT INTO restaurants (name, address, phone, currency, tax_rate, has_electronic_drawer, sla_warning_minutes)
VALUES ('MSEC Restaurant Démo', 'Avenue Kenda, Gombe, Kinshasa', '+243 81 234 5678', 'FC', 0, true, 30)
ON CONFLICT DO NOTHING;

-- Utilisateurs de démo
INSERT INTO users (restaurant_id, name, email, password, role, is_active)
VALUES 
    (1, 'Super Admin', 'superadmin@pos.local', '$2y$12$LJ3m4yS8xQzK8vQzK8vQzK8vQzK8vQzK8vQzK8vQzK8vQzK8vQzK', 'super_admin', true),
    (1, 'Manager Démo', 'manager.demo@msec-pos.com', '$2y$12$Manager2026HashHere', 'manager', true),
    (1, 'Caissier Démo', 'caisse.demo@msec-pos.com', '$2y$12$Caisse2026HashHere', 'cashier', true),
    (1, 'Cuisinier Démo', 'cuisine.demo@msec-pos.com', '$2y$12$Cuisine2026HashHere', 'cook', true)
ON CONFLICT (email) DO NOTHING;

-- Catégories de démo
INSERT INTO categories (restaurant_id, name, icon, display_order, is_active)
VALUES 
    (1, 'Entrées', '🥗', 1, true),
    (1, 'Plats', '🍖', 2, true),
    (1, 'Boissons', '🥤', 3, true),
    (1, 'Desserts', '🍰', 4, true)
ON CONFLICT DO NOTHING;

-- Produits de démo
INSERT INTO products (restaurant_id, category_id, name, price, kitchen_route, prep_time_minutes, is_available)
VALUES 
    (1, 1, 'Salade de Chèvre', 8000, 'kitchen', 10, true),
    (1, 1, 'Soupe du Jour', 5000, 'kitchen', 15, true),
    (1, 2, 'Poulet Braisé', 12000, 'kitchen', 25, true),
    (1, 2, 'Poisson Grillé', 15000, 'kitchen', 20, true),
    (1, 2, 'Riz Sauce Arachide', 7000, 'kitchen', 15, true),
    (1, 3, 'Coca Cola', 3000, 'counter', 0, true),
    (1, 3, 'Jus de Fruits', 4000, 'bar', 5, true),
    (1, 3, 'Bière Locale', 3500, 'bar', 0, true),
    (1, 4, 'Crème Caramel', 4500, 'counter', 5, true)
ON CONFLICT DO NOTHING;

-- Tables de démo
INSERT INTO restaurant_tables (restaurant_id, name, zone, status, capacity, is_active)
VALUES 
    (1, 'Salle 1', 'Salle', 'available', 4, true),
    (1, 'Salle 2', 'Salle', 'available', 4, true),
    (1, 'Salle 3', 'Salle', 'available', 6, true),
    (1, 'Salle 4', 'Salle', 'available', 4, true),
    (1, 'Salle 5', 'Salle', 'available', 4, true),
    (1, 'Salle 6', 'Salle', 'available', 2, true),
    (1, 'Terrasse 1', 'Terrasse', 'available', 4, true),
    (1, 'Terrasse 2', 'Terrasse', 'available', 4, true),
    (1, 'Terrasse 3', 'Terrasse', 'available', 6, true),
    (1, 'Terrasse 4', 'Terrasse', 'available', 2, true),
    (1, 'VIP 1', 'VIP', 'available', 8, true),
    (1, 'VIP 2', 'VIP', 'available', 4, true)
ON CONFLICT DO NOTHING;

-- ═══════════════════════════════════════════════════════════
-- RLS (Row Level Security) — Optionnel mais recommandé
-- ═══════════════════════════════════════════════════════════
-- Activer RLS sur toutes les tables
ALTER TABLE restaurants ENABLE ROW LEVEL SECURITY;
ALTER TABLE users ENABLE ROW LEVEL SECURITY;
ALTER TABLE orders ENABLE ROW LEVEL SECURITY;
ALTER TABLE order_items ENABLE ROW LEVEL SECURITY;
ALTER TABLE products ENABLE ROW LEVEL SECURITY;
ALTER TABLE categories ENABLE ROW LEVEL SECURITY;
ALTER TABLE restaurant_tables ENABLE ROW LEVEL SECURITY;
ALTER TABLE ingredients ENABLE ROW LEVEL SECURITY;
ALTER TABLE waste_logs ENABLE ROW LEVEL SECURITY;
ALTER TABLE cash_shifts ENABLE ROW LEVEL SECURITY;
ALTER TABLE audit_logs ENABLE ROW LEVEL SECURITY;

-- Politiques RLS (accès par restaurant)
CREATE POLICY "Users can view their restaurant data" ON restaurants
    FOR SELECT USING (true);

CREATE POLICY "Users can view their restaurant users" ON users
    FOR SELECT USING (restaurant_id = current_setting('app.current_restaurant_id', true)::bigint);

CREATE POLICY "Users can view their restaurant orders" ON orders
    FOR SELECT USING (restaurant_id = current_setting('app.current_restaurant_id', true)::bigint);

CREATE POLICY "Users can view their restaurant products" ON products
    FOR SELECT USING (restaurant_id = current_setting('app.current_restaurant_id', true)::bigint);

-- ═══════════════════════════════════════════════════════════
-- FONCTION: Mise à jour automatique de updated_at
-- ═══════════════════════════════════════════════════════════
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Triggers pour updated_at
CREATE TRIGGER update_restaurants_updated_at BEFORE UPDATE ON restaurants FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_users_updated_at BEFORE UPDATE ON users FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_orders_updated_at BEFORE UPDATE ON orders FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_products_updated_at BEFORE UPDATE ON products FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_categories_updated_at BEFORE UPDATE ON categories FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_restaurant_tables_updated_at BEFORE UPDATE ON restaurant_tables FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- ═══════════════════════════════════════════════════════════
-- TERMINÉ — Vérifiez avec: SELECT count(*) FROM restaurants;
-- ═══════════════════════════════════════════════════════════
