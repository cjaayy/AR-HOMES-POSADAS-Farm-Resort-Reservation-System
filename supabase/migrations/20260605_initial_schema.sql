-- Supabase starter schema for AR Homes Posadas Farm Resort Reservation System
-- This is a migration seed, not a full one-to-one MySQL port.

create extension if not exists pgcrypto;

create or replace function public.set_updated_at()
returns trigger
language plpgsql
as $$
begin
  new.updated_at = now();
  return new;
end;
$$;

create table if not exists public.profiles (
  id uuid primary key references auth.users(id) on delete cascade,
  user_id text unique,
  username text unique,
  full_name text,
  email text unique,
  phone text,
  avatar_url text,
  role text not null default 'user',
  is_active boolean not null default true,
  email_verified boolean not null default false,
  last_login timestamptz,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

create table if not exists public.admin_users (
  id uuid primary key default gen_random_uuid(),
  admin_id text unique,
  username text unique not null,
  email text unique not null,
  password_hash text,
  full_name text not null,
  role text not null default 'staff',
  position text,
  is_active boolean not null default true,
  created_by text,
  last_login timestamptz,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

create table if not exists public.reservations (
  id uuid primary key default gen_random_uuid(),
  reservation_id text unique not null,
  user_id text not null,
  guest_name text,
  guest_email text,
  guest_phone text,
  booking_type text,
  status text not null default 'pending',
  check_in_date date,
  check_out_date date,
  check_in_time time,
  check_out_time time,
  number_of_days integer,
  number_of_nights integer,
  total_amount numeric(12,2),
  downpayment_amount numeric(12,2),
  downpayment_paid boolean not null default false,
  downpayment_verified boolean not null default false,
  full_payment_paid boolean not null default false,
  paymongo_source_id text,
  paymongo_full_payment_link_id text,
  paymongo_payment_type text,
  admin_notes text,
  user_notes text,
  date_locked boolean not null default false,
  locked_until timestamptz,
  submission_token text,
  created_ip inet,
  checked_in boolean not null default false,
  checked_out boolean not null default false,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

create table if not exists public.reviews (
  review_id bigserial primary key,
  user_id text not null,
  reservation_id text not null unique,
  rating smallint not null check (rating between 1 and 5),
  title text not null,
  content text not null,
  helpful_count integer not null default 0,
  status text not null default 'active',
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

create table if not exists public.review_helpful_votes (
  vote_id bigserial primary key,
  review_id bigint not null references public.reviews(review_id) on delete cascade,
  user_id text not null,
  created_at timestamptz not null default now(),
  unique (review_id, user_id)
);

create table if not exists public.notifications (
  id uuid primary key default gen_random_uuid(),
  user_id text not null,
  type text not null,
  title text not null,
  message text not null,
  link text,
  is_read boolean not null default false,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

create table if not exists public.room_inventory (
  id bigserial primary key,
  room_number text not null unique,
  room_type text,
  status text not null default 'available',
  current_reservation_id text,
  last_updated timestamptz not null default now()
);

create table if not exists public.activity_log (
  id bigserial primary key,
  actor_type text,
  actor_id text,
  action text not null,
  details jsonb,
  created_at timestamptz not null default now()
);

create table if not exists public.reservation_audit_log (
  id bigserial primary key,
  reservation_id text not null,
  action text not null,
  previous_status text,
  new_status text,
  actor_id text,
  actor_type text default 'system',
  details jsonb,
  ip_address inet,
  user_agent text,
  created_at timestamptz not null default now()
);

create table if not exists public.rate_limits (
  id bigserial primary key,
  identifier text not null,
  action text not null,
  attempts integer not null default 1,
  first_attempt timestamptz not null default now(),
  last_attempt timestamptz not null default now(),
  blocked_until timestamptz,
  unique (identifier, action)
);

create table if not exists public.form_submissions (
  id bigserial primary key,
  form_token text not null unique,
  user_id text,
  form_type text not null,
  result_id text,
  ip_address inet,
  created_at timestamptz not null default now()
);

create trigger set_profiles_updated_at
before update on public.profiles
for each row execute function public.set_updated_at();

create trigger set_admin_users_updated_at
before update on public.admin_users
for each row execute function public.set_updated_at();

create trigger set_reservations_updated_at
before update on public.reservations
for each row execute function public.set_updated_at();

create trigger set_reviews_updated_at
before update on public.reviews
for each row execute function public.set_updated_at();

create trigger set_notifications_updated_at
before update on public.notifications
for each row execute function public.set_updated_at();

create index if not exists idx_reservations_user_status on public.reservations (user_id, status, check_in_date);
create index if not exists idx_reservations_status_locked on public.reservations (status, downpayment_paid, locked_until);
create index if not exists idx_reviews_user on public.reviews (user_id);
create index if not exists idx_notifications_user_read on public.notifications (user_id, is_read);
