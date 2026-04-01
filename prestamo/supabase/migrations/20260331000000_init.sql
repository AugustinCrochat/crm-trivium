-- Schema for Supabase - Sistema de Préstamos

-- Tabla de configuración
CREATE TABLE IF NOT EXISTS config (
    key VARCHAR(255) PRIMARY KEY,
    value TEXT NOT NULL
);

-- Insertar valor inicial de tasa de interés
INSERT INTO config (key, value) VALUES ('interest_rate', '10')
ON CONFLICT (key) DO NOTHING;

-- Tabla de prestamistas
CREATE TABLE IF NOT EXISTS lenders (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(50),
    access_code VARCHAR(10) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de préstamos
CREATE TABLE IF NOT EXISTS loans (
    id SERIAL PRIMARY KEY,
    lender_id INTEGER NOT NULL REFERENCES lenders(id) ON DELETE CASCADE,
    amount DECIMAL(15, 2) NOT NULL,
    duration_months INTEGER NOT NULL,
    rate DECIMAL(5, 2) NOT NULL,
    start_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de pagos
CREATE TABLE IF NOT EXISTS payments (
    id SERIAL PRIMARY KEY,
    loan_id INTEGER NOT NULL REFERENCES loans(id) ON DELETE CASCADE,
    amount DECIMAL(15, 2) NOT NULL,
    due_date DATE NOT NULL,
    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'paid')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Índices para mejorar rendimiento
CREATE INDEX IF NOT EXISTS idx_loans_lender_id ON loans(lender_id);
CREATE INDEX IF NOT EXISTS idx_payments_loan_id ON payments(loan_id);
CREATE INDEX IF NOT EXISTS idx_payments_due_date ON payments(due_date);
CREATE INDEX IF NOT EXISTS idx_payments_status ON payments(status);
CREATE INDEX IF NOT EXISTS idx_lenders_access_code ON lenders(access_code);
