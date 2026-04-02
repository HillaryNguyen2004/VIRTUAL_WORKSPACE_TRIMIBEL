-- Add missing columns to data warehouse schema
-- Run this script before running the updated ETL pipeline

-- Create dim_phase table
CREATE TABLE IF NOT EXISTS dim_phase (
    phase_sk SERIAL PRIMARY KEY,
    phase_id BIGINT UNIQUE NOT NULL,
    project_id BIGINT,
    title VARCHAR(255),
    start_date DATE,
    due_date DATE
);

-- Add missing columns to dim_project
ALTER TABLE dim_project ADD COLUMN IF NOT EXISTS description TEXT;
ALTER TABLE dim_project ADD COLUMN IF NOT EXISTS staff_id BIGINT;

-- Add missing columns to dim_task
ALTER TABLE dim_task ADD COLUMN IF NOT EXISTS phase_id BIGINT;
ALTER TABLE dim_task ADD COLUMN IF NOT EXISTS parent_id BIGINT;
ALTER TABLE dim_task ADD COLUMN IF NOT EXISTS start_date DATE;
ALTER TABLE dim_task ADD COLUMN IF NOT EXISTS due_date DATE;
ALTER TABLE dim_task ADD COLUMN IF NOT EXISTS active BOOLEAN DEFAULT TRUE;
ALTER TABLE dim_task ADD COLUMN IF NOT EXISTS assigned_user_id BIGINT;
ALTER TABLE dim_task ADD COLUMN IF NOT EXISTS status VARCHAR(50);

-- Add missing columns to fact_employee_productivity
ALTER TABLE fact_employee_productivity ADD COLUMN IF NOT EXISTS check_in_time TIME;
ALTER TABLE fact_employee_productivity ADD COLUMN IF NOT EXISTS check_out_time TIME;
ALTER TABLE fact_employee_productivity ADD COLUMN IF NOT EXISTS phase_sk INTEGER;

-- Add foreign key for phase_sk
ALTER TABLE fact_employee_productivity
    DROP CONSTRAINT IF EXISTS fk_fact_phase;

ALTER TABLE fact_employee_productivity
    ADD CONSTRAINT fk_fact_phase
    FOREIGN KEY (phase_sk) REFERENCES dim_phase(phase_sk);

-- Create index on phase_id in dim_phase for faster lookups
CREATE INDEX IF NOT EXISTS idx_dim_phase_phase_id ON dim_phase(phase_id);
CREATE INDEX IF NOT EXISTS idx_dim_phase_project_id ON dim_phase(project_id);

-- Create indexes on new columns in dim_task for better performance
CREATE INDEX IF NOT EXISTS idx_dim_task_phase_id ON dim_task(phase_id);
CREATE INDEX IF NOT EXISTS idx_dim_task_parent_id ON dim_task(parent_id);
CREATE INDEX IF NOT EXISTS idx_dim_task_assigned_user_id ON dim_task(assigned_user_id);

COMMIT;
