-- Create restricted user for Laravel POS application
-- This script runs when PostgreSQL container starts

-- Create application user with limited privileges
CREATE USER laravel_pos_app WITH PASSWORD 'laravel_pos_app_password';

-- Grant connect permission to database
GRANT CONNECT ON DATABASE laravel_pos TO laravel_pos_app;

-- Grant usage on schema
GRANT USAGE ON SCHEMA public TO laravel_pos_app;

-- Grant basic permissions (SELECT, INSERT, UPDATE, DELETE) on all tables
-- This will be applied to existing and future tables
ALTER DEFAULT PRIVILEGES IN SCHEMA public
GRANT
SELECT,
INSERT
,
UPDATE,
DELETE ON TABLES TO laravel_pos_app;

-- Grant sequence permissions for auto-increment fields
ALTER DEFAULT PRIVILEGES IN SCHEMA public
GRANT USAGE,
SELECT ON SEQUENCES TO laravel_pos_app;

-- Grant execute permission on functions (if any)
ALTER DEFAULT PRIVILEGES IN SCHEMA public
GRANT
EXECUTE ON FUNCTIONS TO laravel_pos_app;

-- Grant temporary table permissions (needed for migrations)
GRANT TEMPORARY ON DATABASE laravel_pos TO laravel_pos_app;

-- Create tables permission (needed for migrations)
GRANT CREATE ON SCHEMA public TO laravel_pos_app;

-- Grant all privileges on existing tables and sequences
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO laravel_pos_app;

GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO laravel_pos_app;

-- Note: The laravel_pos_admin (admin user) will be created by Docker with full privileges
-- This laravel_pos_app user is for application use with restricted permissions
