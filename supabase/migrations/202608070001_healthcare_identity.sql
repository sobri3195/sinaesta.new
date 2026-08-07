-- SINAESTA healthcare identity, RBAC, consent, and audit foundation.
-- Apply with the Supabase CLI. All access is denied until an explicit policy allows it.
create extension if not exists pgcrypto;

create type public.account_status as enum ('invited','active','inactive','suspended');
create type public.credential_status as enum ('pending','verified','rejected','expired');
create type public.member_status as enum ('invited','active','inactive');

create table public.organizations (
  id uuid primary key default gen_random_uuid(), name text not null, legal_name text,
  registration_number text, created_by uuid not null references auth.users(id),
  created_at timestamptz not null default now(), updated_at timestamptz not null default now()
);
create table public.facilities (
  id uuid primary key default gen_random_uuid(), organization_id uuid not null references public.organizations(id) on delete cascade,
  name text not null, facility_type text not null, address text, active boolean not null default true,
  created_at timestamptz not null default now(), unique (organization_id, name)
);
create table public.profiles (
  id uuid primary key references auth.users(id) on delete cascade, full_name text not null,
  profession text, photo_path text, account_status public.account_status not null default 'active',
  clinical_interests text[] not null default '{}', internal_signature_path text,
  terms_accepted_at timestamptz, privacy_accepted_at timestamptz,
  last_active_at timestamptz, created_at timestamptz not null default now(), updated_at timestamptz not null default now()
);
create table public.roles (id uuid primary key default gen_random_uuid(), code text not null unique, name text not null);
create table public.permissions (id uuid primary key default gen_random_uuid(), code text not null unique, description text not null);
create table public.organization_members (
  id uuid primary key default gen_random_uuid(), organization_id uuid not null references public.organizations(id) on delete cascade,
  facility_id uuid references public.facilities(id) on delete cascade, user_id uuid not null references auth.users(id) on delete cascade,
  role_id uuid not null references public.roles(id), unit_name text, status public.member_status not null default 'invited',
  invited_by uuid references auth.users(id), invited_at timestamptz not null default now(), activated_at timestamptz,
  unique (organization_id, facility_id, user_id, role_id)
);
create table public.role_permissions (role_id uuid references public.roles(id) on delete cascade, permission_id uuid references public.permissions(id) on delete cascade, primary key(role_id, permission_id));
create table public.professional_credentials (
  id uuid primary key default gen_random_uuid(), user_id uuid not null references auth.users(id) on delete cascade,
  facility_id uuid references public.facilities(id), registration_number text not null, practice_license_number text,
  registration_expires_at date, practice_license_expires_at date, document_path text,
  verification_status public.credential_status not null default 'pending', verified_by uuid references auth.users(id), verified_at timestamptz,
  created_at timestamptz not null default now()
);
create table public.user_sessions (
  id uuid primary key default gen_random_uuid(), user_id uuid not null references auth.users(id) on delete cascade,
  session_id uuid not null unique, user_agent text, ip_hash text, last_active_at timestamptz not null default now(), revoked_at timestamptz
);
create table public.consent_records (
  id uuid primary key default gen_random_uuid(), user_id uuid not null references auth.users(id) on delete cascade,
  consent_type text not null check (consent_type in ('terms','privacy')), version text not null,
  accepted boolean not null, recorded_at timestamptz not null default now(), ip_hash text
);
create table public.patient_assignments (
  id uuid primary key default gen_random_uuid(), organization_id uuid not null references public.organizations(id),
  facility_id uuid not null references public.facilities(id), patient_id uuid not null references auth.users(id),
  practitioner_id uuid not null references auth.users(id), reason text not null,
  starts_at timestamptz not null default now(), ends_at timestamptz, created_by uuid not null references auth.users(id)
);
create table public.audit_logs (
  id uuid primary key default gen_random_uuid(), organization_id uuid references public.organizations(id),
  actor_id uuid references auth.users(id), subject_user_id uuid references auth.users(id),
  action text not null check (action in ('login','logout','login_failed','role_changed','permission_changed','medical_record_opened','data_exported','document_downloaded','account_activated','account_deactivated')),
  outcome text not null check (outcome in ('success','failure')), resource_type text, resource_id uuid,
  metadata jsonb not null default '{}', occurred_at timestamptz not null default now()
);

insert into public.roles(code,name) values
 ('super_admin','Super Admin'),('facility_admin','Admin Fasilitas'),('doctor','Dokter'),('dentist','Dokter Gigi'),
 ('nurse','Perawat'),('midwife','Bidan'),('allied_health','Tenaga Kesehatan Lain'),
 ('medical_records_officer','Petugas Rekam Medis'),('patient','Pasien');
insert into public.permissions(code,description) values
 ('members.read','Melihat anggota organisasi'),('members.manage','Mengundang dan mengaktifkan anggota'),
 ('rbac.manage','Mengubah peran dan izin'),('credentials.verify','Memverifikasi kredensial profesional'),
 ('clinical.open_assigned','Membuka rekam medis pasien yang ditugaskan'),('audit.read','Melihat audit log');
insert into public.role_permissions(role_id,permission_id)
select r.id,p.id from public.roles r cross join public.permissions p
where r.code='super_admin' or (r.code='facility_admin' and p.code in ('members.read','members.manage','credentials.verify','audit.read'))
or (r.code in ('doctor','dentist','nurse','midwife','allied_health') and p.code='clinical.open_assigned')
or (r.code='medical_records_officer' and p.code='members.read');

create or replace function public.is_org_member(org_id uuid) returns boolean language sql stable security definer set search_path='' as $$
  select exists(select 1 from public.organization_members m where m.organization_id=org_id and m.user_id=auth.uid() and m.status='active')
$$;
create or replace function public.has_permission(org_id uuid, permission_code text) returns boolean language sql stable security definer set search_path='' as $$
  select exists(select 1 from public.organization_members m join public.role_permissions rp on rp.role_id=m.role_id join public.permissions p on p.id=rp.permission_id where m.organization_id=org_id and m.user_id=auth.uid() and m.status='active' and p.code=permission_code)
$$;
create or replace function public.has_patient_relationship(patient uuid) returns boolean language sql stable security definer set search_path='' as $$
  select patient=auth.uid() or exists(select 1 from public.patient_assignments a where a.patient_id=patient and a.practitioner_id=auth.uid() and a.starts_at<=now() and (a.ends_at is null or a.ends_at>now()))
$$;

alter table public.organizations enable row level security; alter table public.facilities enable row level security;
alter table public.profiles enable row level security; alter table public.professional_credentials enable row level security;
alter table public.organization_members enable row level security; alter table public.roles enable row level security;
alter table public.permissions enable row level security; alter table public.role_permissions enable row level security;
alter table public.user_sessions enable row level security; alter table public.consent_records enable row level security;
alter table public.patient_assignments enable row level security; alter table public.audit_logs enable row level security;

create policy organizations_member_read on public.organizations for select using (public.is_org_member(id));
create policy facilities_member_read on public.facilities for select using (public.is_org_member(organization_id));
create policy profiles_self_read on public.profiles for select using (id=auth.uid());
create policy profiles_colleague_read on public.profiles for select using (exists(select 1 from public.organization_members mine join public.organization_members theirs on theirs.organization_id=mine.organization_id where mine.user_id=auth.uid() and mine.status='active' and theirs.user_id=profiles.id));
create policy profiles_self_update on public.profiles for update using (id=auth.uid()) with check (id=auth.uid());
create policy credentials_owner_read on public.professional_credentials for select using (user_id=auth.uid());
create policy credentials_verifier_read on public.professional_credentials for select using (exists(select 1 from public.facilities f where f.id=facility_id and public.has_permission(f.organization_id,'credentials.verify')));
create policy members_org_read on public.organization_members for select using (public.is_org_member(organization_id));
create policy roles_authenticated_read on public.roles for select to authenticated using (true);
create policy permissions_authenticated_read on public.permissions for select to authenticated using (true);
create policy sessions_self_all on public.user_sessions for all using (user_id=auth.uid()) with check (user_id=auth.uid());
create policy consents_self_all on public.consent_records for all using (user_id=auth.uid()) with check (user_id=auth.uid());
create policy assignments_party_read on public.patient_assignments for select using (patient_id=auth.uid() or practitioner_id=auth.uid());
create policy audit_authorized_read on public.audit_logs for select using (organization_id is not null and public.has_permission(organization_id,'audit.read'));

create or replace function public.bootstrap_organization() returns trigger language plpgsql security definer set search_path='' as $$
declare org_id uuid; facility_id uuid; role_id uuid; org_name text;
begin
  org_name := nullif(trim(new.raw_user_meta_data->>'organization_name'),'');
  insert into public.profiles(id,full_name,terms_accepted_at,privacy_accepted_at)
  values(new.id,coalesce(nullif(new.raw_user_meta_data->>'full_name',''),split_part(new.email,'@',1)),
    case when (new.raw_user_meta_data->>'terms_accepted')::boolean then now() end,
    case when (new.raw_user_meta_data->>'privacy_accepted')::boolean then now() end);
  if org_name is not null then
    insert into public.organizations(name,created_by) values(org_name,new.id) returning id into org_id;
    insert into public.facilities(organization_id,name,facility_type) values(org_id,org_name,'healthcare_facility') returning id into facility_id;
    select id into role_id from public.roles where code='super_admin';
    insert into public.organization_members(organization_id,facility_id,user_id,role_id,status,activated_at) values(org_id,facility_id,new.id,role_id,'active',now());
  end if;
  return new;
end $$;
create trigger auth_user_bootstrap after insert on auth.users for each row execute function public.bootstrap_organization();

create or replace function public.audit_member_access_change() returns trigger language plpgsql security definer set search_path='' as $$
begin
  if tg_op='UPDATE' and old.role_id is distinct from new.role_id then
    insert into public.audit_logs(organization_id,actor_id,subject_user_id,action,outcome,resource_type,resource_id,metadata)
    values(new.organization_id,auth.uid(),new.user_id,'role_changed','success','organization_member',new.id,jsonb_build_object('old_role_id',old.role_id,'new_role_id',new.role_id));
  elsif tg_op='UPDATE' and old.status is distinct from new.status then
    insert into public.audit_logs(organization_id,actor_id,subject_user_id,action,outcome,resource_type,resource_id)
    values(new.organization_id,auth.uid(),new.user_id,case when new.status='active' then 'account_activated' else 'account_deactivated' end,'success','organization_member',new.id);
  end if;
  return new;
end $$;
create trigger organization_member_access_audit after update of role_id,status on public.organization_members for each row execute function public.audit_member_access_change();

-- Role/permission mutations are intentionally service-role-only and therefore have no client write policy.
-- Clinical tables must call has_patient_relationship(patient_id) in their own RLS policies; admin roles receive no bypass.
create index members_user_org_idx on public.organization_members(user_id, organization_id) where status='active';
create index assignments_active_idx on public.patient_assignments(practitioner_id, patient_id, starts_at, ends_at);
create index audit_org_time_idx on public.audit_logs(organization_id, occurred_at desc);
create index credentials_user_idx on public.professional_credentials(user_id, verification_status);
