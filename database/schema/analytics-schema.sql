--
-- PostgreSQL database dump
--

\restrict HVQhcNUVkPPluWTfZ6URnFrpLcphwicM2aAOpLeiHI8R2enLaIQHqmvmfHDSFs5

-- Dumped from database version 17.6
-- Dumped by pg_dump version 17.6

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: pg_trgm; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS pg_trgm WITH SCHEMA public;


--
-- Name: EXTENSION pg_trgm; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON EXTENSION pg_trgm IS 'text similarity measurement and index searching based on trigrams';


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: addresses; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.addresses (
    id bigint NOT NULL,
    title character varying(255),
    user_id bigint,
    street character varying(255),
    city character varying(255),
    pincode character varying(255),
    is_default integer DEFAULT 0 NOT NULL,
    country_code character varying(255),
    phone character varying(255) DEFAULT '0'::character varying,
    country_id bigint,
    state_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: addresses_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.addresses_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: addresses_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.addresses_id_seq OWNED BY public.addresses.id;


--
-- Name: attachments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.attachments (
    id bigint NOT NULL,
    image_url character varying(255),
    model_id character varying(255),
    model_type character varying(255),
    uuid uuid,
    collection_name character varying(255),
    name character varying(255),
    file_name character varying(255),
    mime_type character varying(255),
    disk character varying(255) DEFAULT 'public'::character varying,
    conversions_disk character varying(255) DEFAULT 'public'::character varying,
    size bigint,
    manipulations jsonb,
    custom_properties jsonb,
    generated_conversions jsonb,
    responsive_images jsonb,
    order_column integer,
    created_by_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    original_url character varying(255),
    takealot_url character varying(255),
    media_id bigint
);


--
-- Name: attachments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.attachments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: attachments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.attachments_id_seq OWNED BY public.attachments.id;


--
-- Name: attribute_values; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.attribute_values (
    id bigint NOT NULL,
    value character varying(255),
    hex_color character varying(255),
    slug character varying(255),
    attribute_id bigint,
    created_by_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: attribute_values_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.attribute_values_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: attribute_values_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.attribute_values_id_seq OWNED BY public.attribute_values.id;


--
-- Name: attributes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.attributes (
    id bigint NOT NULL,
    name character varying(255),
    style character varying(255),
    slug character varying(255),
    status integer DEFAULT 1 NOT NULL,
    created_by_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: attributes_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.attributes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: attributes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.attributes_id_seq OWNED BY public.attributes.id;


--
-- Name: auction_events; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.auction_events (
    id bigint NOT NULL,
    auction_item_id bigint NOT NULL,
    event character varying(50) NOT NULL,
    meta json,
    session_id character varying(64),
    ip character varying(45),
    user_agent text,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: auction_events_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.auction_events_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: auction_events_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.auction_events_id_seq OWNED BY public.auction_events.id;


--
-- Name: blog_categories; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.blog_categories (
    id bigint NOT NULL,
    blog_id bigint NOT NULL,
    category_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: blog_categories_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.blog_categories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: blog_categories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.blog_categories_id_seq OWNED BY public.blog_categories.id;


--
-- Name: blog_tags; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.blog_tags (
    id bigint NOT NULL,
    blog_id bigint NOT NULL,
    tag_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: blog_tags_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.blog_tags_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: blog_tags_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.blog_tags_id_seq OWNED BY public.blog_tags.id;


--
-- Name: blogs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.blogs (
    id bigint NOT NULL,
    title text NOT NULL,
    slug character varying(191),
    description text,
    content text,
    meta_title text,
    meta_description text,
    blog_thumbnail_id bigint,
    blog_meta_image_id bigint,
    is_featured integer DEFAULT 0 NOT NULL,
    is_sticky integer DEFAULT 0 NOT NULL,
    status integer DEFAULT 1 NOT NULL,
    created_by_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: blogs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.blogs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: blogs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.blogs_id_seq OWNED BY public.blogs.id;


--
-- Name: cache; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: cart_abandonments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cart_abandonments (
    id bigint NOT NULL,
    session_id character varying(255) NOT NULL,
    user_id bigint,
    email character varying(255),
    cart_items json NOT NULL,
    cart_value numeric(10,2),
    currency character varying(3) DEFAULT 'USD'::character varying NOT NULL,
    items_count integer DEFAULT 0 NOT NULL,
    abandonment_stage character varying(50) NOT NULL,
    abandonment_reason text,
    recovered boolean DEFAULT false NOT NULL,
    order_id bigint,
    recovered_at timestamp(0) without time zone,
    ip_address character varying(45),
    device_type character varying(50),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: cart_abandonments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.cart_abandonments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: cart_abandonments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.cart_abandonments_id_seq OWNED BY public.cart_abandonments.id;


--
-- Name: carts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.carts (
    id bigint NOT NULL,
    product_id bigint,
    variation_id bigint,
    consumer_id bigint,
    quantity integer NOT NULL,
    sub_total numeric(8,2) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    item_shipping_method character varying(255),
    item_shipping_cost numeric(12,2)
);


--
-- Name: carts_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.carts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: carts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.carts_id_seq OWNED BY public.carts.id;


--
-- Name: categories; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.categories (
    id bigint NOT NULL,
    name character varying(255),
    slug character varying(255),
    description text,
    category_image_id bigint,
    category_icon_id bigint,
    status integer DEFAULT 1 NOT NULL,
    type character varying(255) DEFAULT 'product'::character varying NOT NULL,
    commission_rate numeric(8,2) DEFAULT '0'::numeric,
    parent_id bigint,
    created_by_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    category_image_uuid uuid,
    category_icon_uuid uuid
);


--
-- Name: categories_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.categories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: categories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.categories_id_seq OWNED BY public.categories.id;


--
-- Name: commission_histories; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.commission_histories (
    id bigint NOT NULL,
    admin_commission numeric(8,2) DEFAULT '0'::numeric,
    vendor_commission numeric(8,2) DEFAULT '0'::numeric,
    order_id bigint,
    store_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: commission_histories_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.commission_histories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: commission_histories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.commission_histories_id_seq OWNED BY public.commission_histories.id;


--
-- Name: compares; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.compares (
    id bigint NOT NULL,
    product_id bigint,
    variation_id bigint,
    consumer_id bigint,
    category_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: compares_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.compares_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: compares_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.compares_id_seq OWNED BY public.compares.id;


--
-- Name: countries; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.countries (
    id bigint NOT NULL,
    name character varying(255),
    currency character varying(255),
    currency_symbol character varying(255),
    iso_3166_2 character varying(255),
    iso_3166_3 character varying(255),
    calling_code character varying(255),
    flag character varying(6)
);


--
-- Name: countries_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.countries_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: countries_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.countries_id_seq OWNED BY public.countries.id;


--
-- Name: coupons; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.coupons (
    id bigint NOT NULL,
    title character varying(255),
    description text,
    code character varying(255),
    type character varying(255) DEFAULT 'fixed'::character varying,
    amount numeric(15,2) DEFAULT '0'::numeric,
    min_spend numeric(15,2) DEFAULT '0'::numeric,
    is_unlimited integer DEFAULT 1,
    usage_per_coupon integer DEFAULT 0,
    usage_per_customer integer DEFAULT 0,
    used integer DEFAULT 0,
    status integer DEFAULT 1,
    is_expired integer DEFAULT 0,
    is_apply_all integer DEFAULT 0,
    is_first_order integer DEFAULT 0,
    start_date date,
    end_date date,
    created_by_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    CONSTRAINT coupons_type_check CHECK (((type)::text = ANY ((ARRAY['fixed'::character varying, 'free_shipping'::character varying, 'percentage'::character varying])::text[])))
);


--
-- Name: coupons_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.coupons_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: coupons_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.coupons_id_seq OWNED BY public.coupons.id;


--
-- Name: cross_sell_products; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cross_sell_products (
    id bigint NOT NULL,
    product_id bigint,
    cross_sell_product_id bigint,
    deleted_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: cross_sell_products_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.cross_sell_products_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: cross_sell_products_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.cross_sell_products_id_seq OWNED BY public.cross_sell_products.id;


--
-- Name: currencies; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.currencies (
    id bigint NOT NULL,
    code character varying(255),
    symbol character varying(255),
    no_of_decimal numeric(8,2) DEFAULT '2'::numeric,
    exchange_rate numeric(8,2) DEFAULT '1'::numeric,
    symbol_position character varying(255) DEFAULT 'before_price'::character varying,
    thousands_separator character varying(255) DEFAULT 'comma'::character varying,
    decimal_separator character varying(255) DEFAULT 'comma'::character varying,
    system_reserve integer DEFAULT 0 NOT NULL,
    status integer DEFAULT 1,
    created_by_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    CONSTRAINT currencies_decimal_separator_check CHECK (((decimal_separator)::text = ANY ((ARRAY['comma'::character varying, 'period'::character varying, 'space'::character varying])::text[]))),
    CONSTRAINT currencies_symbol_position_check CHECK (((symbol_position)::text = ANY ((ARRAY['before_price'::character varying, 'after_price'::character varying])::text[]))),
    CONSTRAINT currencies_thousands_separator_check CHECK (((thousands_separator)::text = ANY ((ARRAY['comma'::character varying, 'period'::character varying, 'space'::character varying])::text[])))
);


--
-- Name: currencies_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.currencies_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: currencies_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.currencies_id_seq OWNED BY public.currencies.id;


--
-- Name: dpo_zambia_transactions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.dpo_zambia_transactions (
    id bigint NOT NULL,
    order_id bigint NOT NULL,
    trans_id character varying(255),
    transaction_token character varying(255),
    result character varying(255),
    result_code character varying(255),
    result_explanation character varying(255),
    transaction_status character varying(255),
    ccd_approval character varying(255),
    company_ref character varying(255),
    transaction_currency character varying(255),
    payment_amount numeric(18,2),
    customer_name character varying(255),
    customer_phone character varying(255),
    customer_email character varying(255),
    customer_country character varying(255),
    fraud_alert boolean,
    fraud_explanation character varying(255),
    date_created timestamp(0) without time zone,
    date_approved timestamp(0) without time zone,
    raw_response json,
    other_fields json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: dpo_zambia_transactions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.dpo_zambia_transactions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: dpo_zambia_transactions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.dpo_zambia_transactions_id_seq OWNED BY public.dpo_zambia_transactions.id;


--
-- Name: exclude_product_coupons; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.exclude_product_coupons (
    id bigint NOT NULL,
    coupon_id bigint,
    product_id bigint
);


--
-- Name: exclude_product_coupons_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.exclude_product_coupons_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: exclude_product_coupons_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.exclude_product_coupons_id_seq OWNED BY public.exclude_product_coupons.id;


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: faqs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.faqs (
    id bigint NOT NULL,
    title text NOT NULL,
    description text,
    created_by_id bigint NOT NULL,
    status integer DEFAULT 1 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: faqs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.faqs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: faqs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.faqs_id_seq OWNED BY public.faqs.id;


--
-- Name: feedbacks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.feedbacks (
    id bigint NOT NULL,
    reaction character varying(255),
    consumer_id bigint,
    question_and_answer_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    CONSTRAINT feedbacks_reaction_check CHECK (((reaction)::text = ANY ((ARRAY['liked'::character varying, 'disliked'::character varying])::text[])))
);


--
-- Name: feedbacks_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.feedbacks_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: feedbacks_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.feedbacks_id_seq OWNED BY public.feedbacks.id;


--
-- Name: home_pages; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.home_pages (
    id bigint NOT NULL,
    content jsonb,
    slug character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: home_pages_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.home_pages_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: home_pages_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.home_pages_id_seq OWNED BY public.home_pages.id;


--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


--
-- Name: jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: media; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.media (
    id bigint NOT NULL,
    model_type character varying(255) NOT NULL,
    model_id bigint NOT NULL,
    uuid uuid,
    collection_name character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    file_name character varying(255) NOT NULL,
    mime_type character varying(255),
    disk character varying(255) NOT NULL,
    conversions_disk character varying(255),
    size bigint NOT NULL,
    manipulations jsonb NOT NULL,
    custom_properties jsonb NOT NULL,
    generated_conversions jsonb NOT NULL,
    responsive_images jsonb NOT NULL,
    order_column integer,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: media_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.media_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: media_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.media_id_seq OWNED BY public.media.id;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.migrations (
    id bigint NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.migrations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: model_has_permissions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.model_has_permissions (
    permission_id bigint NOT NULL,
    model_type character varying(255) NOT NULL,
    model_id bigint NOT NULL
);


--
-- Name: model_has_roles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.model_has_roles (
    role_id bigint NOT NULL,
    model_type character varying(255) NOT NULL,
    model_id bigint NOT NULL
);


--
-- Name: module_permissions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.module_permissions (
    id bigint NOT NULL,
    name character varying(255),
    module_id bigint,
    permission_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: module_permissions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.module_permissions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: module_permissions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.module_permissions_id_seq OWNED BY public.module_permissions.id;


--
-- Name: modules; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.modules (
    id bigint NOT NULL,
    name character varying(255),
    sequence integer,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: modules_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.modules_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: modules_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.modules_id_seq OWNED BY public.modules.id;


--
-- Name: notifications; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.notifications (
    id uuid NOT NULL,
    type character varying(255) NOT NULL,
    notifiable_type character varying(255) NOT NULL,
    notifiable_id bigint NOT NULL,
    data text NOT NULL,
    read_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: order_notes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.order_notes (
    id bigint NOT NULL,
    order_id bigint NOT NULL,
    note text NOT NULL,
    privacy character varying(255) DEFAULT 'private'::character varying NOT NULL,
    created_by_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    CONSTRAINT order_notes_privacy_check CHECK (((privacy)::text = ANY ((ARRAY['public'::character varying, 'private'::character varying])::text[])))
);


--
-- Name: order_notes_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.order_notes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: order_notes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.order_notes_id_seq OWNED BY public.order_notes.id;


--
-- Name: order_products; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.order_products (
    id bigint NOT NULL,
    order_id bigint NOT NULL,
    product_id bigint NOT NULL,
    variation_id bigint,
    quantity integer,
    single_price numeric(8,2),
    shipping_cost numeric(8,2),
    tax numeric(8,2),
    subtotal numeric(8,2),
    refund_status character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    fast_shipping_cost numeric(8,2) DEFAULT '0'::numeric NOT NULL,
    item_shipping_method character varying(255),
    has_fast_shipping boolean DEFAULT false NOT NULL,
    item_status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    cancellation_reason text,
    eta date,
    selected_attribute_ids json,
    variation_display_name character varying(255)
);


--
-- Name: order_products_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.order_products_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: order_products_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.order_products_id_seq OWNED BY public.order_products.id;


--
-- Name: order_status; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.order_status (
    id bigint NOT NULL,
    name character varying(255),
    slug character varying(255),
    sequence integer,
    created_by_id bigint,
    status integer DEFAULT 1 NOT NULL,
    system_reserve integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: order_status_histories; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.order_status_histories (
    id bigint NOT NULL,
    order_id bigint NOT NULL,
    old_status_id bigint,
    new_status_id bigint NOT NULL,
    updated_by_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: order_status_histories_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.order_status_histories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: order_status_histories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.order_status_histories_id_seq OWNED BY public.order_status_histories.id;


--
-- Name: order_status_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.order_status_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: order_status_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.order_status_id_seq OWNED BY public.order_status.id;


--
-- Name: order_transactions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.order_transactions (
    id bigint NOT NULL,
    transaction_id character varying(255),
    order_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: order_transactions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.order_transactions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: order_transactions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.order_transactions_id_seq OWNED BY public.order_transactions.id;


--
-- Name: orders; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.orders (
    id bigint NOT NULL,
    order_number integer,
    consumer_id bigint NOT NULL,
    tax_total numeric(8,2),
    shipping_total numeric(8,2),
    points_amount numeric(8,2),
    wallet_balance numeric(8,2),
    amount numeric(8,2),
    total numeric(8,2),
    coupon_total_discount numeric(8,2),
    payment_method character varying(255),
    payment_status character varying(255) DEFAULT 'PENDING'::character varying,
    store_id bigint,
    billing_address_id bigint,
    shipping_address_id bigint,
    delivery_description character varying(255),
    delivery_interval character varying(255),
    order_status_id bigint,
    coupon_id bigint,
    parent_id bigint,
    created_by_id bigint,
    invoice_url character varying(255),
    status integer DEFAULT 1 NOT NULL,
    delivered_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    delivery_price numeric(8,2) DEFAULT '0'::numeric,
    note text,
    currency character varying(10) DEFAULT 'USD'::character varying,
    currency_symbol character varying(5) DEFAULT '$'::character varying,
    exchange_rate numeric(12,6) DEFAULT '1'::numeric,
    fast_shipping_total numeric(12,2) DEFAULT '0'::numeric NOT NULL
);


--
-- Name: orders_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.orders_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: orders_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.orders_id_seq OWNED BY public.orders.id;


--
-- Name: page_views; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.page_views (
    id bigint NOT NULL,
    session_id character varying(255) NOT NULL,
    user_id bigint,
    url character varying(500) NOT NULL,
    path character varying(500) NOT NULL,
    page_title character varying(255),
    referrer character varying(500),
    utm_source character varying(255),
    utm_medium character varying(255),
    utm_campaign character varying(255),
    utm_term character varying(255),
    utm_content character varying(255),
    ip_address character varying(45),
    user_agent character varying(500),
    device_type character varying(50),
    browser character varying(100),
    os character varying(100),
    country character varying(100),
    city character varying(100),
    duration integer,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    is_bot boolean DEFAULT false NOT NULL
);


--
-- Name: page_views_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.page_views_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: page_views_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.page_views_id_seq OWNED BY public.page_views.id;


--
-- Name: pages; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.pages (
    id bigint NOT NULL,
    title text,
    slug character varying(191),
    content text,
    meta_title text,
    meta_description text,
    page_meta_image_id bigint,
    status integer DEFAULT 1 NOT NULL,
    created_by_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: pages_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.pages_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: pages_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.pages_id_seq OWNED BY public.pages.id;


--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


--
-- Name: payfast_transactions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.payfast_transactions (
    id bigint NOT NULL,
    user_id bigint,
    m_payment_id character varying(255),
    pf_payment_id character varying(255),
    payment_status character varying(255),
    item_name character varying(255),
    item_description character varying(255),
    amount_gross numeric(10,2),
    amount_fee numeric(10,2),
    amount_net numeric(10,2),
    custom_str1 character varying(255),
    custom_str2 character varying(255),
    custom_str3 character varying(255),
    custom_str4 character varying(255),
    custom_str5 character varying(255),
    custom_int1 integer,
    custom_int2 integer,
    custom_int3 integer,
    custom_int4 integer,
    custom_int5 integer,
    name_first character varying(255),
    name_last character varying(255),
    email_address character varying(255),
    merchant_id bigint,
    signature character varying(255),
    response json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: payfast_transactions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.payfast_transactions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: payfast_transactions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.payfast_transactions_id_seq OWNED BY public.payfast_transactions.id;


--
-- Name: payment_accounts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.payment_accounts (
    id bigint NOT NULL,
    user_id bigint,
    paypal_email character varying(255),
    bank_name character varying(255),
    bank_holder_name character varying(255),
    bank_account_no character varying(255),
    swift character varying(255),
    ifsc character varying(255),
    is_default integer DEFAULT 0 NOT NULL,
    status integer DEFAULT 1 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: payment_accounts_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.payment_accounts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: payment_accounts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.payment_accounts_id_seq OWNED BY public.payment_accounts.id;


--
-- Name: permissions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.permissions (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    guard_name character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: permissions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.permissions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: permissions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.permissions_id_seq OWNED BY public.permissions.id;


--
-- Name: personal_access_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.personal_access_tokens (
    id bigint NOT NULL,
    tokenable_type character varying(255) NOT NULL,
    tokenable_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    token character varying(64) NOT NULL,
    abilities text,
    role_type character varying(255),
    last_used_at timestamp(0) without time zone,
    expires_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.personal_access_tokens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.personal_access_tokens_id_seq OWNED BY public.personal_access_tokens.id;


--
-- Name: pesepay_transactions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.pesepay_transactions (
    id bigint NOT NULL,
    user_id bigint,
    api_id bigint,
    reference_number character varying(255) NOT NULL,
    internal_reference character varying(255),
    merchant_reference character varying(255),
    application_id integer,
    application_name character varying(255),
    amount numeric(15,2),
    currency_code character varying(10),
    transaction_status character varying(255),
    transaction_status_code integer,
    transaction_status_description character varying(255),
    transaction_type character varying(255),
    charge_type character varying(255),
    liquidation_status character varying(255),
    liquidation_transaction_reference character varying(255),
    settlement_mode character varying(255),
    redirect_required boolean DEFAULT false NOT NULL,
    poll_url character varying(255),
    redirect_url character varying(255),
    result_url character varying(255),
    return_url character varying(255),
    reason_for_payment text,
    amount_details json,
    customer json,
    customer_amount_paid json,
    payment_metadata json,
    payment_method_details json,
    transaction_metadata json,
    date_of_transaction timestamp(0) without time zone,
    local_date_time_of_transaction timestamp(0) without time zone,
    transaction_date timestamp(0) without time zone,
    time_of_transaction timestamp(0) without time zone,
    response json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: pesepay_transactions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.pesepay_transactions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: pesepay_transactions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.pesepay_transactions_id_seq OWNED BY public.pesepay_transactions.id;


--
-- Name: points; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.points (
    id bigint NOT NULL,
    consumer_id bigint,
    balance numeric(8,2) DEFAULT '0'::numeric NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: points_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.points_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: points_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.points_id_seq OWNED BY public.points.id;


--
-- Name: product_attributes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.product_attributes (
    id bigint NOT NULL,
    attribute_id bigint NOT NULL,
    product_id bigint NOT NULL,
    deleted_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: product_attributes_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.product_attributes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: product_attributes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.product_attributes_id_seq OWNED BY public.product_attributes.id;


--
-- Name: product_categories; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.product_categories (
    id bigint NOT NULL,
    product_id bigint NOT NULL,
    category_id bigint NOT NULL,
    deleted_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: product_categories_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.product_categories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: product_categories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.product_categories_id_seq OWNED BY public.product_categories.id;


--
-- Name: product_coupons; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.product_coupons (
    id bigint NOT NULL,
    coupon_id bigint,
    product_id bigint
);


--
-- Name: product_coupons_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.product_coupons_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: product_coupons_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.product_coupons_id_seq OWNED BY public.product_coupons.id;


--
-- Name: product_images; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.product_images (
    id bigint NOT NULL,
    product_id bigint,
    attachment_id bigint,
    deleted_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: product_images_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.product_images_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: product_images_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.product_images_id_seq OWNED BY public.product_images.id;


--
-- Name: product_tags; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.product_tags (
    id bigint NOT NULL,
    tag_id bigint NOT NULL,
    product_id bigint NOT NULL,
    deleted_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: product_tags_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.product_tags_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: product_tags_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.product_tags_id_seq OWNED BY public.product_tags.id;


--
-- Name: products; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.products (
    id bigint NOT NULL,
    name character varying(255),
    slug character varying(255),
    short_description text,
    description text,
    type character varying(255),
    unit character varying(255),
    weight integer,
    quantity integer,
    price numeric(8,2),
    sale_price numeric(8,2),
    discount numeric(8,2),
    is_featured integer DEFAULT 0,
    shipping_days integer DEFAULT 0,
    is_cod integer DEFAULT 0 NOT NULL,
    is_free_shipping integer DEFAULT 0,
    is_sale_enable integer DEFAULT 0,
    is_return integer DEFAULT 0,
    is_trending integer DEFAULT 0,
    is_approved integer DEFAULT 1,
    is_external integer DEFAULT 0,
    external_url character varying(255),
    external_button_text character varying(255),
    sale_starts_at character varying(255),
    sale_expired_at character varying(255),
    sku character varying(255) NOT NULL,
    is_random_related_products integer DEFAULT 0,
    stock_status character varying(255),
    meta_title character varying(255),
    meta_description text,
    product_thumbnail_id bigint,
    product_meta_image_id bigint,
    size_chart_image_id bigint,
    estimated_delivery_text character varying(255),
    return_policy_text text,
    safe_checkout integer DEFAULT 1,
    secure_checkout integer DEFAULT 1,
    social_share integer DEFAULT 1,
    encourage_order integer DEFAULT 1,
    encourage_view integer DEFAULT 1,
    status integer DEFAULT 1 NOT NULL,
    store_id bigint,
    created_by_id bigint,
    tax_id bigint,
    deleted_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    search_keywords text,
    search_tsv tsvector GENERATED ALWAYS AS (to_tsvector('english'::regconfig, search_keywords)) STORED,
    has_expedited_shipping boolean DEFAULT false NOT NULL,
    standard_shipping_days character varying(255),
    expedited_shipping_days character varying(255),
    standard_shipping_price numeric(12,2),
    expedited_shipping_price numeric(12,2),
    CONSTRAINT products_stock_status_check CHECK (((stock_status)::text = ANY ((ARRAY['in_stock'::character varying, 'out_of_stock'::character varying])::text[]))),
    CONSTRAINT products_type_check CHECK (((type)::text = ANY ((ARRAY['simple'::character varying, 'classified'::character varying])::text[])))
);


--
-- Name: products_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.products_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: products_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.products_id_seq OWNED BY public.products.id;


--
-- Name: question_and_answers; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.question_and_answers (
    id bigint NOT NULL,
    question text,
    answer text,
    consumer_id bigint,
    product_id bigint,
    store_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: question_and_answers_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.question_and_answers_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: question_and_answers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.question_and_answers_id_seq OWNED BY public.question_and_answers.id;


--
-- Name: refunds; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.refunds (
    id bigint NOT NULL,
    reason character varying(255),
    amount numeric(8,2) DEFAULT '0'::numeric,
    quantity integer DEFAULT 0,
    store_id bigint,
    order_id bigint,
    product_id bigint,
    consumer_id bigint,
    variation_id bigint,
    refund_image_id bigint,
    payment_type character varying(255) DEFAULT 'wallet'::character varying,
    status character varying(255) DEFAULT 'pending'::character varying,
    is_used integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    CONSTRAINT refunds_payment_type_check CHECK (((payment_type)::text = ANY ((ARRAY['wallet'::character varying, 'paypal'::character varying, 'bank'::character varying])::text[]))),
    CONSTRAINT refunds_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'approved'::character varying, 'rejected'::character varying])::text[])))
);


--
-- Name: refunds_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.refunds_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: refunds_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.refunds_id_seq OWNED BY public.refunds.id;


--
-- Name: related_products; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.related_products (
    id bigint NOT NULL,
    product_id bigint,
    related_product_id bigint,
    deleted_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: related_products_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.related_products_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: related_products_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.related_products_id_seq OWNED BY public.related_products.id;


--
-- Name: returns; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.returns (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    order_id bigint NOT NULL,
    product_id bigint NOT NULL,
    return_reason character varying(255) NOT NULL,
    sub_reason character varying(255),
    description text,
    product_not_used boolean DEFAULT false NOT NULL,
    in_original_packaging boolean DEFAULT false NOT NULL,
    include_all_accessories boolean DEFAULT false NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    preferred_outcome character varying(255),
    rejection_reason text
);


--
-- Name: returns_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.returns_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: returns_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.returns_id_seq OWNED BY public.returns.id;


--
-- Name: reviews; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.reviews (
    id bigint NOT NULL,
    product_id bigint,
    consumer_id bigint,
    store_id bigint,
    review_image_id bigint,
    rating numeric(8,2) DEFAULT '0'::numeric,
    description text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: reviews_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.reviews_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: reviews_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.reviews_id_seq OWNED BY public.reviews.id;


--
-- Name: role_has_permissions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.role_has_permissions (
    permission_id bigint NOT NULL,
    role_id bigint NOT NULL
);


--
-- Name: roles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.roles (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    guard_name character varying(255) NOT NULL,
    system_reserve integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: roles_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.roles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: roles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.roles_id_seq OWNED BY public.roles.id;


--
-- Name: seeders; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.seeders (
    id bigint NOT NULL,
    name character varying(255),
    is_completed integer DEFAULT 0
);


--
-- Name: seeders_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.seeders_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: seeders_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.seeders_id_seq OWNED BY public.seeders.id;


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


--
-- Name: settings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.settings (
    id bigint NOT NULL,
    "values" jsonb,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: settings_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.settings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: settings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.settings_id_seq OWNED BY public.settings.id;


--
-- Name: shipping_rules; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.shipping_rules (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    shipping_id integer NOT NULL,
    rule_type character varying(255) DEFAULT 'base_on_price'::character varying,
    min numeric(15,2) DEFAULT '0'::numeric,
    max numeric(15,2) DEFAULT '0'::numeric,
    shipping_type character varying(255) DEFAULT 'free'::character varying,
    amount numeric(15,2) DEFAULT '0'::numeric,
    status integer DEFAULT 1,
    created_by_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    CONSTRAINT shipping_rules_rule_type_check CHECK (((rule_type)::text = ANY ((ARRAY['base_on_price'::character varying, 'base_on_weight'::character varying])::text[]))),
    CONSTRAINT shipping_rules_shipping_type_check CHECK (((shipping_type)::text = ANY ((ARRAY['free'::character varying, 'fixed'::character varying, 'percentage'::character varying])::text[])))
);


--
-- Name: shipping_rules_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.shipping_rules_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: shipping_rules_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.shipping_rules_id_seq OWNED BY public.shipping_rules.id;


--
-- Name: shippings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.shippings (
    id bigint NOT NULL,
    status integer DEFAULT 1,
    country_id bigint,
    created_by_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: shippings_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.shippings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: shippings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.shippings_id_seq OWNED BY public.shippings.id;


--
-- Name: states; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.states (
    id bigint NOT NULL,
    name character varying(255),
    country_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: states_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.states_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: states_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.states_id_seq OWNED BY public.states.id;


--
-- Name: stores; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.stores (
    id bigint NOT NULL,
    store_name character varying(255),
    description text,
    slug character varying(255),
    store_logo_id bigint,
    store_cover_id bigint,
    country_id bigint,
    state_id bigint,
    city character varying(255),
    address character varying(255),
    pincode character varying(255),
    facebook character varying(255),
    twitter character varying(255),
    instagram character varying(255),
    youtube character varying(255),
    pinterest character varying(255),
    hide_vendor_email integer DEFAULT 1,
    hide_vendor_phone integer DEFAULT 1,
    vendor_id bigint,
    created_by_id bigint,
    status integer DEFAULT 1 NOT NULL,
    is_approved integer DEFAULT 1,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: stores_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.stores_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: stores_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.stores_id_seq OWNED BY public.stores.id;


--
-- Name: tags; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tags (
    id bigint NOT NULL,
    name character varying(255),
    slug character varying(255),
    type character varying(255) DEFAULT 'post'::character varying NOT NULL,
    description text,
    created_by_id bigint NOT NULL,
    status integer DEFAULT 1,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: tags_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.tags_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: tags_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.tags_id_seq OWNED BY public.tags.id;


--
-- Name: taxes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.taxes (
    id bigint NOT NULL,
    name character varying(255),
    rate numeric(8,2),
    status integer DEFAULT 1 NOT NULL,
    created_by_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: taxes_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.taxes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: taxes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.taxes_id_seq OWNED BY public.taxes.id;


--
-- Name: telescope_entries; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.telescope_entries (
    sequence bigint NOT NULL,
    uuid uuid NOT NULL,
    batch_id uuid NOT NULL,
    family_hash character varying(255),
    should_display_on_index boolean DEFAULT true NOT NULL,
    type character varying(20) NOT NULL,
    content text NOT NULL,
    created_at timestamp(0) without time zone
);


--
-- Name: telescope_entries_sequence_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.telescope_entries_sequence_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: telescope_entries_sequence_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.telescope_entries_sequence_seq OWNED BY public.telescope_entries.sequence;


--
-- Name: telescope_entries_tags; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.telescope_entries_tags (
    entry_uuid uuid NOT NULL,
    tag character varying(255) NOT NULL
);


--
-- Name: telescope_monitoring; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.telescope_monitoring (
    tag character varying(255) NOT NULL
);


--
-- Name: theme_options; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.theme_options (
    id bigint NOT NULL,
    options jsonb,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: theme_options_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.theme_options_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: theme_options_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.theme_options_id_seq OWNED BY public.theme_options.id;


--
-- Name: themes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.themes (
    id bigint NOT NULL,
    name character varying(255),
    slug character varying(255),
    image character varying(255),
    status integer DEFAULT 1 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: themes_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.themes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: themes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.themes_id_seq OWNED BY public.themes.id;


--
-- Name: transactions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.transactions (
    id bigint NOT NULL,
    wallet_id bigint,
    order_id bigint,
    point_id bigint,
    amount numeric(8,2) DEFAULT '0'::numeric NOT NULL,
    type character varying(255),
    detail character varying(255),
    "from" bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    CONSTRAINT transactions_type_check CHECK (((type)::text = ANY ((ARRAY['credit'::character varying, 'debit'::character varying])::text[])))
);


--
-- Name: transactions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.transactions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: transactions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.transactions_id_seq OWNED BY public.transactions.id;


--
-- Name: user_events; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.user_events (
    id bigint NOT NULL,
    session_id character varying(255) NOT NULL,
    user_id bigint,
    event_type character varying(100) NOT NULL,
    event_category character varying(100),
    event_name character varying(200) NOT NULL,
    event_data json,
    page_url character varying(500),
    element_id character varying(255),
    element_class character varying(255),
    element_text text,
    value numeric(10,2),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    is_bot boolean DEFAULT false NOT NULL
);


--
-- Name: user_events_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.user_events_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: user_events_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.user_events_id_seq OWNED BY public.user_events.id;


--
-- Name: user_sessions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.user_sessions (
    id bigint NOT NULL,
    session_id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent character varying(500),
    device_type character varying(50),
    browser character varying(100),
    os character varying(100),
    platform character varying(100),
    country character varying(100),
    city character varying(100),
    landing_page character varying(500),
    referrer character varying(500),
    started_at timestamp(0) without time zone,
    last_activity_at timestamp(0) without time zone,
    ended_at timestamp(0) without time zone,
    total_page_views integer DEFAULT 0 NOT NULL,
    total_events integer DEFAULT 0 NOT NULL,
    duration integer,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    is_bot boolean DEFAULT false NOT NULL
);


--
-- Name: user_sessions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.user_sessions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: user_sessions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.user_sessions_id_seq OWNED BY public.user_sessions.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255),
    country_code character varying(255),
    phone character varying(255) DEFAULT '0'::character varying,
    profile_image_id bigint,
    password character varying(255) NOT NULL,
    system_reserve integer DEFAULT 0 NOT NULL,
    status integer DEFAULT 1 NOT NULL,
    created_by_id bigint,
    email_verified_at timestamp(0) without time zone,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    preferred_currency character varying(255) DEFAULT 'USD'::character varying NOT NULL,
    currency_symbol character varying(255) DEFAULT '$'::character varying NOT NULL,
    currency_exchange_rate numeric(10,4) DEFAULT '1'::numeric NOT NULL
);


--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: variation_attribute_values; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.variation_attribute_values (
    id bigint NOT NULL,
    attribute_value_id bigint NOT NULL,
    variation_id bigint NOT NULL,
    deleted_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: variation_attribute_values_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.variation_attribute_values_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: variation_attribute_values_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.variation_attribute_values_id_seq OWNED BY public.variation_attribute_values.id;


--
-- Name: variations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.variations (
    id bigint NOT NULL,
    name character varying(255),
    price numeric(8,2),
    quantity integer,
    stock_status character varying(255),
    sale_price numeric(8,2),
    discount numeric(8,2),
    sku character varying(255),
    status integer DEFAULT 1 NOT NULL,
    variation_options jsonb,
    variation_image_id bigint,
    product_id bigint,
    deleted_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT variations_stock_status_check CHECK (((stock_status)::text = ANY ((ARRAY['in_stock'::character varying, 'out_of_stock'::character varying, 'coming_soon'::character varying])::text[])))
);


--
-- Name: variations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.variations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: variations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.variations_id_seq OWNED BY public.variations.id;


--
-- Name: vendor_transactions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.vendor_transactions (
    id bigint NOT NULL,
    vendor_wallet_id bigint,
    vendor_id bigint,
    amount numeric(8,2) DEFAULT '0'::numeric NOT NULL,
    type character varying(255),
    detail character varying(255),
    "from" bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    CONSTRAINT vendor_transactions_type_check CHECK (((type)::text = ANY ((ARRAY['credit'::character varying, 'debit'::character varying])::text[])))
);


--
-- Name: vendor_transactions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.vendor_transactions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: vendor_transactions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.vendor_transactions_id_seq OWNED BY public.vendor_transactions.id;


--
-- Name: vendor_wallets; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.vendor_wallets (
    id bigint NOT NULL,
    vendor_id bigint,
    balance numeric(8,2) DEFAULT '0'::numeric NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: vendor_wallets_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.vendor_wallets_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: vendor_wallets_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.vendor_wallets_id_seq OWNED BY public.vendor_wallets.id;


--
-- Name: wallets; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.wallets (
    id bigint NOT NULL,
    consumer_id bigint,
    balance numeric(8,2) DEFAULT '0'::numeric NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: wallets_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.wallets_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: wallets_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.wallets_id_seq OWNED BY public.wallets.id;


--
-- Name: wishlists; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.wishlists (
    id bigint NOT NULL,
    product_id bigint NOT NULL,
    consumer_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: wishlists_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.wishlists_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: wishlists_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.wishlists_id_seq OWNED BY public.wishlists.id;


--
-- Name: withdraw_requests; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.withdraw_requests (
    id bigint NOT NULL,
    amount numeric(8,2) DEFAULT '0'::numeric,
    message character varying(255),
    status character varying(255) DEFAULT 'pending'::character varying,
    vendor_wallet_id bigint,
    vendor_id bigint,
    payment_type character varying(255) DEFAULT 'bank'::character varying,
    is_used integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    CONSTRAINT withdraw_requests_payment_type_check CHECK (((payment_type)::text = ANY ((ARRAY['paypal'::character varying, 'bank'::character varying])::text[]))),
    CONSTRAINT withdraw_requests_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'approved'::character varying, 'rejected'::character varying])::text[])))
);


--
-- Name: withdraw_requests_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.withdraw_requests_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: withdraw_requests_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.withdraw_requests_id_seq OWNED BY public.withdraw_requests.id;


--
-- Name: yoco_transactions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.yoco_transactions (
    id bigint NOT NULL,
    order_id bigint,
    gateway_transaction_id character varying(255),
    status character varying(255),
    amount_cents bigint,
    currency character varying(10),
    raw_response json,
    other_fields json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    order_number character varying(255)
);


--
-- Name: yoco_transactions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.yoco_transactions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: yoco_transactions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.yoco_transactions_id_seq OWNED BY public.yoco_transactions.id;


--
-- Name: addresses id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.addresses ALTER COLUMN id SET DEFAULT nextval('public.addresses_id_seq'::regclass);


--
-- Name: attachments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.attachments ALTER COLUMN id SET DEFAULT nextval('public.attachments_id_seq'::regclass);


--
-- Name: attribute_values id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.attribute_values ALTER COLUMN id SET DEFAULT nextval('public.attribute_values_id_seq'::regclass);


--
-- Name: attributes id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.attributes ALTER COLUMN id SET DEFAULT nextval('public.attributes_id_seq'::regclass);


--
-- Name: auction_events id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.auction_events ALTER COLUMN id SET DEFAULT nextval('public.auction_events_id_seq'::regclass);


--
-- Name: blog_categories id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.blog_categories ALTER COLUMN id SET DEFAULT nextval('public.blog_categories_id_seq'::regclass);


--
-- Name: blog_tags id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.blog_tags ALTER COLUMN id SET DEFAULT nextval('public.blog_tags_id_seq'::regclass);


--
-- Name: blogs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.blogs ALTER COLUMN id SET DEFAULT nextval('public.blogs_id_seq'::regclass);


--
-- Name: cart_abandonments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cart_abandonments ALTER COLUMN id SET DEFAULT nextval('public.cart_abandonments_id_seq'::regclass);


--
-- Name: carts id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.carts ALTER COLUMN id SET DEFAULT nextval('public.carts_id_seq'::regclass);


--
-- Name: categories id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categories ALTER COLUMN id SET DEFAULT nextval('public.categories_id_seq'::regclass);


--
-- Name: commission_histories id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.commission_histories ALTER COLUMN id SET DEFAULT nextval('public.commission_histories_id_seq'::regclass);


--
-- Name: compares id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.compares ALTER COLUMN id SET DEFAULT nextval('public.compares_id_seq'::regclass);


--
-- Name: countries id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.countries ALTER COLUMN id SET DEFAULT nextval('public.countries_id_seq'::regclass);


--
-- Name: coupons id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.coupons ALTER COLUMN id SET DEFAULT nextval('public.coupons_id_seq'::regclass);


--
-- Name: cross_sell_products id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cross_sell_products ALTER COLUMN id SET DEFAULT nextval('public.cross_sell_products_id_seq'::regclass);


--
-- Name: currencies id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.currencies ALTER COLUMN id SET DEFAULT nextval('public.currencies_id_seq'::regclass);


--
-- Name: dpo_zambia_transactions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.dpo_zambia_transactions ALTER COLUMN id SET DEFAULT nextval('public.dpo_zambia_transactions_id_seq'::regclass);


--
-- Name: exclude_product_coupons id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.exclude_product_coupons ALTER COLUMN id SET DEFAULT nextval('public.exclude_product_coupons_id_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: faqs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.faqs ALTER COLUMN id SET DEFAULT nextval('public.faqs_id_seq'::regclass);


--
-- Name: feedbacks id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.feedbacks ALTER COLUMN id SET DEFAULT nextval('public.feedbacks_id_seq'::regclass);


--
-- Name: home_pages id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.home_pages ALTER COLUMN id SET DEFAULT nextval('public.home_pages_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: media id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.media ALTER COLUMN id SET DEFAULT nextval('public.media_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: module_permissions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.module_permissions ALTER COLUMN id SET DEFAULT nextval('public.module_permissions_id_seq'::regclass);


--
-- Name: modules id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.modules ALTER COLUMN id SET DEFAULT nextval('public.modules_id_seq'::regclass);


--
-- Name: order_notes id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_notes ALTER COLUMN id SET DEFAULT nextval('public.order_notes_id_seq'::regclass);


--
-- Name: order_products id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_products ALTER COLUMN id SET DEFAULT nextval('public.order_products_id_seq'::regclass);


--
-- Name: order_status id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_status ALTER COLUMN id SET DEFAULT nextval('public.order_status_id_seq'::regclass);


--
-- Name: order_status_histories id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_status_histories ALTER COLUMN id SET DEFAULT nextval('public.order_status_histories_id_seq'::regclass);


--
-- Name: order_transactions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_transactions ALTER COLUMN id SET DEFAULT nextval('public.order_transactions_id_seq'::regclass);


--
-- Name: orders id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orders ALTER COLUMN id SET DEFAULT nextval('public.orders_id_seq'::regclass);


--
-- Name: page_views id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.page_views ALTER COLUMN id SET DEFAULT nextval('public.page_views_id_seq'::regclass);


--
-- Name: pages id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pages ALTER COLUMN id SET DEFAULT nextval('public.pages_id_seq'::regclass);


--
-- Name: payfast_transactions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payfast_transactions ALTER COLUMN id SET DEFAULT nextval('public.payfast_transactions_id_seq'::regclass);


--
-- Name: payment_accounts id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_accounts ALTER COLUMN id SET DEFAULT nextval('public.payment_accounts_id_seq'::regclass);


--
-- Name: permissions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permissions ALTER COLUMN id SET DEFAULT nextval('public.permissions_id_seq'::regclass);


--
-- Name: personal_access_tokens id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.personal_access_tokens ALTER COLUMN id SET DEFAULT nextval('public.personal_access_tokens_id_seq'::regclass);


--
-- Name: pesepay_transactions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pesepay_transactions ALTER COLUMN id SET DEFAULT nextval('public.pesepay_transactions_id_seq'::regclass);


--
-- Name: points id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.points ALTER COLUMN id SET DEFAULT nextval('public.points_id_seq'::regclass);


--
-- Name: product_attributes id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_attributes ALTER COLUMN id SET DEFAULT nextval('public.product_attributes_id_seq'::regclass);


--
-- Name: product_categories id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_categories ALTER COLUMN id SET DEFAULT nextval('public.product_categories_id_seq'::regclass);


--
-- Name: product_coupons id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_coupons ALTER COLUMN id SET DEFAULT nextval('public.product_coupons_id_seq'::regclass);


--
-- Name: product_images id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_images ALTER COLUMN id SET DEFAULT nextval('public.product_images_id_seq'::regclass);


--
-- Name: product_tags id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_tags ALTER COLUMN id SET DEFAULT nextval('public.product_tags_id_seq'::regclass);


--
-- Name: products id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products ALTER COLUMN id SET DEFAULT nextval('public.products_id_seq'::regclass);


--
-- Name: question_and_answers id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.question_and_answers ALTER COLUMN id SET DEFAULT nextval('public.question_and_answers_id_seq'::regclass);


--
-- Name: refunds id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.refunds ALTER COLUMN id SET DEFAULT nextval('public.refunds_id_seq'::regclass);


--
-- Name: related_products id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.related_products ALTER COLUMN id SET DEFAULT nextval('public.related_products_id_seq'::regclass);


--
-- Name: returns id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.returns ALTER COLUMN id SET DEFAULT nextval('public.returns_id_seq'::regclass);


--
-- Name: reviews id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reviews ALTER COLUMN id SET DEFAULT nextval('public.reviews_id_seq'::regclass);


--
-- Name: roles id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles ALTER COLUMN id SET DEFAULT nextval('public.roles_id_seq'::regclass);


--
-- Name: seeders id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.seeders ALTER COLUMN id SET DEFAULT nextval('public.seeders_id_seq'::regclass);


--
-- Name: settings id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.settings ALTER COLUMN id SET DEFAULT nextval('public.settings_id_seq'::regclass);


--
-- Name: shipping_rules id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.shipping_rules ALTER COLUMN id SET DEFAULT nextval('public.shipping_rules_id_seq'::regclass);


--
-- Name: shippings id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.shippings ALTER COLUMN id SET DEFAULT nextval('public.shippings_id_seq'::regclass);


--
-- Name: states id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.states ALTER COLUMN id SET DEFAULT nextval('public.states_id_seq'::regclass);


--
-- Name: stores id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stores ALTER COLUMN id SET DEFAULT nextval('public.stores_id_seq'::regclass);


--
-- Name: tags id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tags ALTER COLUMN id SET DEFAULT nextval('public.tags_id_seq'::regclass);


--
-- Name: taxes id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.taxes ALTER COLUMN id SET DEFAULT nextval('public.taxes_id_seq'::regclass);


--
-- Name: telescope_entries sequence; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.telescope_entries ALTER COLUMN sequence SET DEFAULT nextval('public.telescope_entries_sequence_seq'::regclass);


--
-- Name: theme_options id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.theme_options ALTER COLUMN id SET DEFAULT nextval('public.theme_options_id_seq'::regclass);


--
-- Name: themes id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.themes ALTER COLUMN id SET DEFAULT nextval('public.themes_id_seq'::regclass);


--
-- Name: transactions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.transactions ALTER COLUMN id SET DEFAULT nextval('public.transactions_id_seq'::regclass);


--
-- Name: user_events id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_events ALTER COLUMN id SET DEFAULT nextval('public.user_events_id_seq'::regclass);


--
-- Name: user_sessions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_sessions ALTER COLUMN id SET DEFAULT nextval('public.user_sessions_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Name: variation_attribute_values id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.variation_attribute_values ALTER COLUMN id SET DEFAULT nextval('public.variation_attribute_values_id_seq'::regclass);


--
-- Name: variations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.variations ALTER COLUMN id SET DEFAULT nextval('public.variations_id_seq'::regclass);


--
-- Name: vendor_transactions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vendor_transactions ALTER COLUMN id SET DEFAULT nextval('public.vendor_transactions_id_seq'::regclass);


--
-- Name: vendor_wallets id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vendor_wallets ALTER COLUMN id SET DEFAULT nextval('public.vendor_wallets_id_seq'::regclass);


--
-- Name: wallets id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.wallets ALTER COLUMN id SET DEFAULT nextval('public.wallets_id_seq'::regclass);


--
-- Name: wishlists id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.wishlists ALTER COLUMN id SET DEFAULT nextval('public.wishlists_id_seq'::regclass);


--
-- Name: withdraw_requests id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.withdraw_requests ALTER COLUMN id SET DEFAULT nextval('public.withdraw_requests_id_seq'::regclass);


--
-- Name: yoco_transactions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.yoco_transactions ALTER COLUMN id SET DEFAULT nextval('public.yoco_transactions_id_seq'::regclass);


--
-- Name: addresses addresses_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.addresses
    ADD CONSTRAINT addresses_pkey PRIMARY KEY (id);


--
-- Name: attachments attachments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.attachments
    ADD CONSTRAINT attachments_pkey PRIMARY KEY (id);


--
-- Name: attachments attachments_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.attachments
    ADD CONSTRAINT attachments_uuid_unique UNIQUE (uuid);


--
-- Name: attribute_values attribute_values_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.attribute_values
    ADD CONSTRAINT attribute_values_pkey PRIMARY KEY (id);


--
-- Name: attributes attributes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.attributes
    ADD CONSTRAINT attributes_pkey PRIMARY KEY (id);


--
-- Name: auction_events auction_events_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.auction_events
    ADD CONSTRAINT auction_events_pkey PRIMARY KEY (id);


--
-- Name: blog_categories blog_categories_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.blog_categories
    ADD CONSTRAINT blog_categories_pkey PRIMARY KEY (id);


--
-- Name: blog_tags blog_tags_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.blog_tags
    ADD CONSTRAINT blog_tags_pkey PRIMARY KEY (id);


--
-- Name: blogs blogs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.blogs
    ADD CONSTRAINT blogs_pkey PRIMARY KEY (id);


--
-- Name: blogs blogs_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.blogs
    ADD CONSTRAINT blogs_slug_unique UNIQUE (slug);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: cart_abandonments cart_abandonments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cart_abandonments
    ADD CONSTRAINT cart_abandonments_pkey PRIMARY KEY (id);


--
-- Name: carts carts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.carts
    ADD CONSTRAINT carts_pkey PRIMARY KEY (id);


--
-- Name: categories categories_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_pkey PRIMARY KEY (id);


--
-- Name: commission_histories commission_histories_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.commission_histories
    ADD CONSTRAINT commission_histories_pkey PRIMARY KEY (id);


--
-- Name: compares compares_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.compares
    ADD CONSTRAINT compares_pkey PRIMARY KEY (id);


--
-- Name: countries countries_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.countries
    ADD CONSTRAINT countries_pkey PRIMARY KEY (id);


--
-- Name: coupons coupons_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.coupons
    ADD CONSTRAINT coupons_pkey PRIMARY KEY (id);


--
-- Name: cross_sell_products cross_sell_products_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cross_sell_products
    ADD CONSTRAINT cross_sell_products_pkey PRIMARY KEY (id);


--
-- Name: currencies currencies_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.currencies
    ADD CONSTRAINT currencies_pkey PRIMARY KEY (id);


--
-- Name: dpo_zambia_transactions dpo_zambia_transactions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.dpo_zambia_transactions
    ADD CONSTRAINT dpo_zambia_transactions_pkey PRIMARY KEY (id);


--
-- Name: exclude_product_coupons exclude_product_coupons_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.exclude_product_coupons
    ADD CONSTRAINT exclude_product_coupons_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: faqs faqs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.faqs
    ADD CONSTRAINT faqs_pkey PRIMARY KEY (id);


--
-- Name: feedbacks feedbacks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.feedbacks
    ADD CONSTRAINT feedbacks_pkey PRIMARY KEY (id);


--
-- Name: home_pages home_pages_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.home_pages
    ADD CONSTRAINT home_pages_pkey PRIMARY KEY (id);


--
-- Name: home_pages home_pages_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.home_pages
    ADD CONSTRAINT home_pages_slug_unique UNIQUE (slug);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: media media_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.media
    ADD CONSTRAINT media_pkey PRIMARY KEY (id);


--
-- Name: media media_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.media
    ADD CONSTRAINT media_uuid_unique UNIQUE (uuid);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: model_has_permissions model_has_permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.model_has_permissions
    ADD CONSTRAINT model_has_permissions_pkey PRIMARY KEY (permission_id, model_id, model_type);


--
-- Name: model_has_roles model_has_roles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.model_has_roles
    ADD CONSTRAINT model_has_roles_pkey PRIMARY KEY (role_id, model_id, model_type);


--
-- Name: module_permissions module_permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.module_permissions
    ADD CONSTRAINT module_permissions_pkey PRIMARY KEY (id);


--
-- Name: modules modules_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.modules
    ADD CONSTRAINT modules_pkey PRIMARY KEY (id);


--
-- Name: notifications notifications_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.notifications
    ADD CONSTRAINT notifications_pkey PRIMARY KEY (id);


--
-- Name: order_notes order_notes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_notes
    ADD CONSTRAINT order_notes_pkey PRIMARY KEY (id);


--
-- Name: order_products order_products_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_products
    ADD CONSTRAINT order_products_pkey PRIMARY KEY (id);


--
-- Name: order_status_histories order_status_histories_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_status_histories
    ADD CONSTRAINT order_status_histories_pkey PRIMARY KEY (id);


--
-- Name: order_status order_status_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_status
    ADD CONSTRAINT order_status_pkey PRIMARY KEY (id);


--
-- Name: order_transactions order_transactions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_transactions
    ADD CONSTRAINT order_transactions_pkey PRIMARY KEY (id);


--
-- Name: orders orders_order_number_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orders
    ADD CONSTRAINT orders_order_number_unique UNIQUE (order_number);


--
-- Name: orders orders_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orders
    ADD CONSTRAINT orders_pkey PRIMARY KEY (id);


--
-- Name: page_views page_views_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.page_views
    ADD CONSTRAINT page_views_pkey PRIMARY KEY (id);


--
-- Name: pages pages_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pages
    ADD CONSTRAINT pages_pkey PRIMARY KEY (id);


--
-- Name: pages pages_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pages
    ADD CONSTRAINT pages_slug_unique UNIQUE (slug);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: payfast_transactions payfast_transactions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payfast_transactions
    ADD CONSTRAINT payfast_transactions_pkey PRIMARY KEY (id);


--
-- Name: payment_accounts payment_accounts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_accounts
    ADD CONSTRAINT payment_accounts_pkey PRIMARY KEY (id);


--
-- Name: permissions permissions_name_guard_name_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permissions
    ADD CONSTRAINT permissions_name_guard_name_unique UNIQUE (name, guard_name);


--
-- Name: permissions permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permissions
    ADD CONSTRAINT permissions_pkey PRIMARY KEY (id);


--
-- Name: personal_access_tokens personal_access_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_pkey PRIMARY KEY (id);


--
-- Name: personal_access_tokens personal_access_tokens_token_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_token_unique UNIQUE (token);


--
-- Name: pesepay_transactions pesepay_transactions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pesepay_transactions
    ADD CONSTRAINT pesepay_transactions_pkey PRIMARY KEY (id);


--
-- Name: pesepay_transactions pesepay_transactions_reference_number_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pesepay_transactions
    ADD CONSTRAINT pesepay_transactions_reference_number_unique UNIQUE (reference_number);


--
-- Name: points points_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.points
    ADD CONSTRAINT points_pkey PRIMARY KEY (id);


--
-- Name: product_attributes product_attributes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_attributes
    ADD CONSTRAINT product_attributes_pkey PRIMARY KEY (id);


--
-- Name: product_categories product_categories_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_categories
    ADD CONSTRAINT product_categories_pkey PRIMARY KEY (id);


--
-- Name: product_coupons product_coupons_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_coupons
    ADD CONSTRAINT product_coupons_pkey PRIMARY KEY (id);


--
-- Name: product_images product_images_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_images
    ADD CONSTRAINT product_images_pkey PRIMARY KEY (id);


--
-- Name: product_tags product_tags_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_tags
    ADD CONSTRAINT product_tags_pkey PRIMARY KEY (id);


--
-- Name: products products_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_pkey PRIMARY KEY (id);


--
-- Name: products products_sku_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_sku_unique UNIQUE (sku);


--
-- Name: question_and_answers question_and_answers_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.question_and_answers
    ADD CONSTRAINT question_and_answers_pkey PRIMARY KEY (id);


--
-- Name: refunds refunds_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.refunds
    ADD CONSTRAINT refunds_pkey PRIMARY KEY (id);


--
-- Name: related_products related_products_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.related_products
    ADD CONSTRAINT related_products_pkey PRIMARY KEY (id);


--
-- Name: returns returns_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.returns
    ADD CONSTRAINT returns_pkey PRIMARY KEY (id);


--
-- Name: returns returns_user_order_product_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.returns
    ADD CONSTRAINT returns_user_order_product_unique UNIQUE (user_id, order_id, product_id);


--
-- Name: reviews reviews_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reviews
    ADD CONSTRAINT reviews_pkey PRIMARY KEY (id);


--
-- Name: role_has_permissions role_has_permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_has_permissions
    ADD CONSTRAINT role_has_permissions_pkey PRIMARY KEY (permission_id, role_id);


--
-- Name: roles roles_name_guard_name_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_name_guard_name_unique UNIQUE (name, guard_name);


--
-- Name: roles roles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_pkey PRIMARY KEY (id);


--
-- Name: seeders seeders_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.seeders
    ADD CONSTRAINT seeders_pkey PRIMARY KEY (id);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: settings settings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.settings
    ADD CONSTRAINT settings_pkey PRIMARY KEY (id);


--
-- Name: shipping_rules shipping_rules_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.shipping_rules
    ADD CONSTRAINT shipping_rules_pkey PRIMARY KEY (id);


--
-- Name: shippings shippings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.shippings
    ADD CONSTRAINT shippings_pkey PRIMARY KEY (id);


--
-- Name: states states_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.states
    ADD CONSTRAINT states_pkey PRIMARY KEY (id);


--
-- Name: stores stores_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stores
    ADD CONSTRAINT stores_pkey PRIMARY KEY (id);


--
-- Name: tags tags_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tags
    ADD CONSTRAINT tags_pkey PRIMARY KEY (id);


--
-- Name: tags tags_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tags
    ADD CONSTRAINT tags_slug_unique UNIQUE (slug);


--
-- Name: taxes taxes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.taxes
    ADD CONSTRAINT taxes_pkey PRIMARY KEY (id);


--
-- Name: telescope_entries telescope_entries_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.telescope_entries
    ADD CONSTRAINT telescope_entries_pkey PRIMARY KEY (sequence);


--
-- Name: telescope_entries telescope_entries_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.telescope_entries
    ADD CONSTRAINT telescope_entries_uuid_unique UNIQUE (uuid);


--
-- Name: theme_options theme_options_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.theme_options
    ADD CONSTRAINT theme_options_pkey PRIMARY KEY (id);


--
-- Name: themes themes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.themes
    ADD CONSTRAINT themes_pkey PRIMARY KEY (id);


--
-- Name: transactions transactions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_pkey PRIMARY KEY (id);


--
-- Name: user_events user_events_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_events
    ADD CONSTRAINT user_events_pkey PRIMARY KEY (id);


--
-- Name: user_sessions user_sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_sessions
    ADD CONSTRAINT user_sessions_pkey PRIMARY KEY (id);


--
-- Name: user_sessions user_sessions_session_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_sessions
    ADD CONSTRAINT user_sessions_session_id_unique UNIQUE (session_id);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: variation_attribute_values variation_attribute_values_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.variation_attribute_values
    ADD CONSTRAINT variation_attribute_values_pkey PRIMARY KEY (id);


--
-- Name: variations variations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.variations
    ADD CONSTRAINT variations_pkey PRIMARY KEY (id);


--
-- Name: variations variations_product_name_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.variations
    ADD CONSTRAINT variations_product_name_unique UNIQUE (product_id, name);


--
-- Name: variations variations_sku_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.variations
    ADD CONSTRAINT variations_sku_unique UNIQUE (sku);


--
-- Name: vendor_transactions vendor_transactions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vendor_transactions
    ADD CONSTRAINT vendor_transactions_pkey PRIMARY KEY (id);


--
-- Name: vendor_wallets vendor_wallets_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vendor_wallets
    ADD CONSTRAINT vendor_wallets_pkey PRIMARY KEY (id);


--
-- Name: wallets wallets_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.wallets
    ADD CONSTRAINT wallets_pkey PRIMARY KEY (id);


--
-- Name: wishlists wishlists_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.wishlists
    ADD CONSTRAINT wishlists_pkey PRIMARY KEY (id);


--
-- Name: withdraw_requests withdraw_requests_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.withdraw_requests
    ADD CONSTRAINT withdraw_requests_pkey PRIMARY KEY (id);


--
-- Name: yoco_transactions yoco_transactions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.yoco_transactions
    ADD CONSTRAINT yoco_transactions_pkey PRIMARY KEY (id);


--
-- Name: attachments_disk_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX attachments_disk_index ON public.attachments USING btree (disk);


--
-- Name: attachments_model_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX attachments_model_id_index ON public.attachments USING btree (model_id);


--
-- Name: attachments_model_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX attachments_model_type_index ON public.attachments USING btree (model_type);


--
-- Name: attachments_order_column_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX attachments_order_column_index ON public.attachments USING btree (order_column);


--
-- Name: attribute_values_attribute_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX attribute_values_attribute_id_index ON public.attribute_values USING btree (attribute_id);


--
-- Name: attribute_values_created_by_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX attribute_values_created_by_id_index ON public.attribute_values USING btree (created_by_id);


--
-- Name: attribute_values_slug_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX attribute_values_slug_index ON public.attribute_values USING btree (slug);


--
-- Name: attributes_created_by_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX attributes_created_by_id_index ON public.attributes USING btree (created_by_id);


--
-- Name: attributes_slug_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX attributes_slug_index ON public.attributes USING btree (slug);


--
-- Name: auction_events_auction_item_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX auction_events_auction_item_id_index ON public.auction_events USING btree (auction_item_id);


--
-- Name: auction_events_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX auction_events_created_at_index ON public.auction_events USING btree (created_at);


--
-- Name: auction_events_event_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX auction_events_event_index ON public.auction_events USING btree (event);


--
-- Name: auction_events_session_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX auction_events_session_id_index ON public.auction_events USING btree (session_id);


--
-- Name: cart_abandonments_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cart_abandonments_created_at_index ON public.cart_abandonments USING btree (created_at);


--
-- Name: cart_abandonments_email_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cart_abandonments_email_index ON public.cart_abandonments USING btree (email);


--
-- Name: cart_abandonments_recovered_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cart_abandonments_recovered_index ON public.cart_abandonments USING btree (recovered);


--
-- Name: cart_abandonments_session_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cart_abandonments_session_id_index ON public.cart_abandonments USING btree (session_id);


--
-- Name: cart_abandonments_user_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cart_abandonments_user_id_created_at_index ON public.cart_abandonments USING btree (user_id, created_at);


--
-- Name: cart_abandonments_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cart_abandonments_user_id_index ON public.cart_abandonments USING btree (user_id);


--
-- Name: categories_created_by_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX categories_created_by_id_index ON public.categories USING btree (created_by_id);


--
-- Name: categories_parent_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX categories_parent_id_index ON public.categories USING btree (parent_id);


--
-- Name: categories_slug_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX categories_slug_index ON public.categories USING btree (slug);


--
-- Name: cross_sell_products_cross_sell_product_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cross_sell_products_cross_sell_product_id_index ON public.cross_sell_products USING btree (cross_sell_product_id);


--
-- Name: cross_sell_products_product_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cross_sell_products_product_id_index ON public.cross_sell_products USING btree (product_id);


--
-- Name: dpo_zambia_transactions_order_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX dpo_zambia_transactions_order_id_index ON public.dpo_zambia_transactions USING btree (order_id);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: media_model_type_model_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX media_model_type_model_id_index ON public.media USING btree (model_type, model_id);


--
-- Name: media_order_column_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX media_order_column_index ON public.media USING btree (order_column);


--
-- Name: model_has_permissions_model_id_model_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX model_has_permissions_model_id_model_type_index ON public.model_has_permissions USING btree (model_id, model_type);


--
-- Name: model_has_roles_model_id_model_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX model_has_roles_model_id_model_type_index ON public.model_has_roles USING btree (model_id, model_type);


--
-- Name: notifications_notifiable_type_notifiable_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX notifications_notifiable_type_notifiable_id_index ON public.notifications USING btree (notifiable_type, notifiable_id);


--
-- Name: order_notes_order_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX order_notes_order_id_index ON public.order_notes USING btree (order_id);


--
-- Name: order_notes_privacy_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX order_notes_privacy_index ON public.order_notes USING btree (privacy);


--
-- Name: order_status_histories_order_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX order_status_histories_order_id_index ON public.order_status_histories USING btree (order_id);


--
-- Name: page_views_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX page_views_created_at_index ON public.page_views USING btree (created_at);


--
-- Name: page_views_is_bot_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX page_views_is_bot_index ON public.page_views USING btree (is_bot);


--
-- Name: page_views_session_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX page_views_session_id_index ON public.page_views USING btree (session_id);


--
-- Name: page_views_user_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX page_views_user_id_created_at_index ON public.page_views USING btree (user_id, created_at);


--
-- Name: page_views_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX page_views_user_id_index ON public.page_views USING btree (user_id);


--
-- Name: personal_access_tokens_tokenable_type_tokenable_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX personal_access_tokens_tokenable_type_tokenable_id_index ON public.personal_access_tokens USING btree (tokenable_type, tokenable_id);


--
-- Name: pesepay_transactions_api_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX pesepay_transactions_api_id_index ON public.pesepay_transactions USING btree (api_id);


--
-- Name: pesepay_transactions_transaction_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX pesepay_transactions_transaction_status_index ON public.pesepay_transactions USING btree (transaction_status);


--
-- Name: pesepay_transactions_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX pesepay_transactions_user_id_index ON public.pesepay_transactions USING btree (user_id);


--
-- Name: product_attributes_product_id_attribute_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX product_attributes_product_id_attribute_id_index ON public.product_attributes USING btree (product_id, attribute_id);


--
-- Name: product_categories_product_id_category_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX product_categories_product_id_category_id_index ON public.product_categories USING btree (product_id, category_id);


--
-- Name: product_images_attachment_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX product_images_attachment_id_index ON public.product_images USING btree (attachment_id);


--
-- Name: product_images_product_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX product_images_product_id_index ON public.product_images USING btree (product_id);


--
-- Name: product_tags_product_id_tag_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX product_tags_product_id_tag_id_index ON public.product_tags USING btree (product_id, tag_id);


--
-- Name: products_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX products_created_at_index ON public.products USING btree (created_at);


--
-- Name: products_name_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX products_name_index ON public.products USING btree (name);


--
-- Name: products_price_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX products_price_index ON public.products USING btree (price);


--
-- Name: products_search_keywords_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX products_search_keywords_idx ON public.products USING gin (to_tsvector('english'::regconfig, search_keywords));


--
-- Name: products_search_keywords_trgm_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX products_search_keywords_trgm_idx ON public.products USING gin (lower(search_keywords) public.gin_trgm_ops);


--
-- Name: products_search_tsv_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX products_search_tsv_idx ON public.products USING gin (search_tsv);


--
-- Name: products_sku_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX products_sku_index ON public.products USING btree (sku);


--
-- Name: products_slug_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX products_slug_index ON public.products USING btree (slug);


--
-- Name: related_products_product_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX related_products_product_id_index ON public.related_products USING btree (product_id);


--
-- Name: related_products_related_product_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX related_products_related_product_id_index ON public.related_products USING btree (related_product_id);


--
-- Name: reviews_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX reviews_created_at_index ON public.reviews USING btree (created_at);


--
-- Name: reviews_product_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX reviews_product_id_index ON public.reviews USING btree (product_id);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: tags_created_by_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tags_created_by_id_index ON public.tags USING btree (created_by_id);


--
-- Name: tags_slug_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tags_slug_index ON public.tags USING btree (slug);


--
-- Name: telescope_entries_batch_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX telescope_entries_batch_id_index ON public.telescope_entries USING btree (batch_id);


--
-- Name: telescope_entries_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX telescope_entries_created_at_index ON public.telescope_entries USING btree (created_at);


--
-- Name: telescope_entries_family_hash_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX telescope_entries_family_hash_index ON public.telescope_entries USING btree (family_hash);


--
-- Name: telescope_entries_tags_entry_uuid_tag_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX telescope_entries_tags_entry_uuid_tag_index ON public.telescope_entries_tags USING btree (entry_uuid, tag);


--
-- Name: telescope_entries_tags_tag_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX telescope_entries_tags_tag_index ON public.telescope_entries_tags USING btree (tag);


--
-- Name: telescope_entries_type_should_display_on_index_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX telescope_entries_type_should_display_on_index_index ON public.telescope_entries USING btree (type, should_display_on_index);


--
-- Name: user_events_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_events_created_at_index ON public.user_events USING btree (created_at);


--
-- Name: user_events_event_type_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_events_event_type_created_at_index ON public.user_events USING btree (event_type, created_at);


--
-- Name: user_events_is_bot_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_events_is_bot_index ON public.user_events USING btree (is_bot);


--
-- Name: user_events_session_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_events_session_id_index ON public.user_events USING btree (session_id);


--
-- Name: user_events_user_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_events_user_id_created_at_index ON public.user_events USING btree (user_id, created_at);


--
-- Name: user_events_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_events_user_id_index ON public.user_events USING btree (user_id);


--
-- Name: user_sessions_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_sessions_created_at_index ON public.user_sessions USING btree (created_at);


--
-- Name: user_sessions_is_bot_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_sessions_is_bot_index ON public.user_sessions USING btree (is_bot);


--
-- Name: user_sessions_last_activity_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_sessions_last_activity_at_index ON public.user_sessions USING btree (last_activity_at);


--
-- Name: user_sessions_user_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_sessions_user_id_created_at_index ON public.user_sessions USING btree (user_id, created_at);


--
-- Name: user_sessions_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_sessions_user_id_index ON public.user_sessions USING btree (user_id);


--
-- Name: variation_attribute_values_attribute_value_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX variation_attribute_values_attribute_value_id_index ON public.variation_attribute_values USING btree (attribute_value_id);


--
-- Name: variation_attribute_values_variation_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX variation_attribute_values_variation_id_index ON public.variation_attribute_values USING btree (variation_id);


--
-- Name: variations_product_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX variations_product_id_index ON public.variations USING btree (product_id);


--
-- Name: variations_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX variations_status_index ON public.variations USING btree (status);


--
-- Name: variations_stock_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX variations_stock_status_index ON public.variations USING btree (stock_status);


--
-- Name: variations_variation_image_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX variations_variation_image_id_index ON public.variations USING btree (variation_image_id);


--
-- Name: yoco_transactions_gateway_transaction_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX yoco_transactions_gateway_transaction_id_index ON public.yoco_transactions USING btree (gateway_transaction_id);


--
-- Name: yoco_transactions_order_id_gateway_transaction_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX yoco_transactions_order_id_gateway_transaction_id_index ON public.yoco_transactions USING btree (order_id, gateway_transaction_id);


--
-- Name: yoco_transactions_order_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX yoco_transactions_order_id_index ON public.yoco_transactions USING btree (order_id);


--
-- Name: yoco_transactions_order_number_gateway_transaction_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX yoco_transactions_order_number_gateway_transaction_id_index ON public.yoco_transactions USING btree (order_number, gateway_transaction_id);


--
-- Name: yoco_transactions_order_number_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX yoco_transactions_order_number_index ON public.yoco_transactions USING btree (order_number);


--
-- Name: yoco_transactions_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX yoco_transactions_status_index ON public.yoco_transactions USING btree (status);


--
-- Name: addresses addresses_country_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.addresses
    ADD CONSTRAINT addresses_country_id_foreign FOREIGN KEY (country_id) REFERENCES public.countries(id) ON DELETE CASCADE;


--
-- Name: addresses addresses_state_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.addresses
    ADD CONSTRAINT addresses_state_id_foreign FOREIGN KEY (state_id) REFERENCES public.states(id) ON DELETE CASCADE;


--
-- Name: addresses addresses_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.addresses
    ADD CONSTRAINT addresses_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: attachments attachments_created_by_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.attachments
    ADD CONSTRAINT attachments_created_by_id_foreign FOREIGN KEY (created_by_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: attribute_values attribute_values_attribute_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.attribute_values
    ADD CONSTRAINT attribute_values_attribute_id_foreign FOREIGN KEY (attribute_id) REFERENCES public.attributes(id) ON DELETE CASCADE;


--
-- Name: attribute_values attribute_values_created_by_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.attribute_values
    ADD CONSTRAINT attribute_values_created_by_id_foreign FOREIGN KEY (created_by_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: attributes attributes_created_by_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.attributes
    ADD CONSTRAINT attributes_created_by_id_foreign FOREIGN KEY (created_by_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: blog_categories blog_categories_blog_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.blog_categories
    ADD CONSTRAINT blog_categories_blog_id_foreign FOREIGN KEY (blog_id) REFERENCES public.blogs(id) ON DELETE CASCADE;


--
-- Name: blog_categories blog_categories_category_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.blog_categories
    ADD CONSTRAINT blog_categories_category_id_foreign FOREIGN KEY (category_id) REFERENCES public.categories(id) ON DELETE CASCADE;


--
-- Name: blog_tags blog_tags_blog_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.blog_tags
    ADD CONSTRAINT blog_tags_blog_id_foreign FOREIGN KEY (blog_id) REFERENCES public.blogs(id) ON DELETE CASCADE;


--
-- Name: blog_tags blog_tags_tag_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.blog_tags
    ADD CONSTRAINT blog_tags_tag_id_foreign FOREIGN KEY (tag_id) REFERENCES public.tags(id) ON DELETE CASCADE;


--
-- Name: blogs blogs_blog_meta_image_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.blogs
    ADD CONSTRAINT blogs_blog_meta_image_id_foreign FOREIGN KEY (blog_meta_image_id) REFERENCES public.attachments(id) ON DELETE CASCADE;


--
-- Name: blogs blogs_blog_thumbnail_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.blogs
    ADD CONSTRAINT blogs_blog_thumbnail_id_foreign FOREIGN KEY (blog_thumbnail_id) REFERENCES public.attachments(id) ON DELETE CASCADE;


--
-- Name: blogs blogs_created_by_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.blogs
    ADD CONSTRAINT blogs_created_by_id_foreign FOREIGN KEY (created_by_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: carts carts_consumer_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.carts
    ADD CONSTRAINT carts_consumer_id_foreign FOREIGN KEY (consumer_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: carts carts_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.carts
    ADD CONSTRAINT carts_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: carts carts_variation_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.carts
    ADD CONSTRAINT carts_variation_id_foreign FOREIGN KEY (variation_id) REFERENCES public.variations(id) ON DELETE CASCADE;


--
-- Name: categories categories_category_icon_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_category_icon_id_foreign FOREIGN KEY (category_icon_id) REFERENCES public.attachments(id) ON DELETE CASCADE;


--
-- Name: categories categories_category_image_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_category_image_id_foreign FOREIGN KEY (category_image_id) REFERENCES public.attachments(id) ON DELETE CASCADE;


--
-- Name: categories categories_created_by_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_created_by_id_foreign FOREIGN KEY (created_by_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: categories categories_parent_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_parent_id_foreign FOREIGN KEY (parent_id) REFERENCES public.categories(id) ON DELETE CASCADE;


--
-- Name: commission_histories commission_histories_order_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.commission_histories
    ADD CONSTRAINT commission_histories_order_id_foreign FOREIGN KEY (order_id) REFERENCES public.orders(id) ON DELETE CASCADE;


--
-- Name: commission_histories commission_histories_store_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.commission_histories
    ADD CONSTRAINT commission_histories_store_id_foreign FOREIGN KEY (store_id) REFERENCES public.stores(id) ON DELETE CASCADE;


--
-- Name: compares compares_category_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.compares
    ADD CONSTRAINT compares_category_id_foreign FOREIGN KEY (category_id) REFERENCES public.categories(id) ON DELETE CASCADE;


--
-- Name: compares compares_consumer_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.compares
    ADD CONSTRAINT compares_consumer_id_foreign FOREIGN KEY (consumer_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: compares compares_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.compares
    ADD CONSTRAINT compares_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: compares compares_variation_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.compares
    ADD CONSTRAINT compares_variation_id_foreign FOREIGN KEY (variation_id) REFERENCES public.variations(id) ON DELETE CASCADE;


--
-- Name: coupons coupons_created_by_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.coupons
    ADD CONSTRAINT coupons_created_by_id_foreign FOREIGN KEY (created_by_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: cross_sell_products cross_sell_products_cross_sell_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cross_sell_products
    ADD CONSTRAINT cross_sell_products_cross_sell_product_id_foreign FOREIGN KEY (cross_sell_product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: cross_sell_products cross_sell_products_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cross_sell_products
    ADD CONSTRAINT cross_sell_products_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: currencies currencies_created_by_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.currencies
    ADD CONSTRAINT currencies_created_by_id_foreign FOREIGN KEY (created_by_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: dpo_zambia_transactions dpo_zambia_transactions_order_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.dpo_zambia_transactions
    ADD CONSTRAINT dpo_zambia_transactions_order_id_foreign FOREIGN KEY (order_id) REFERENCES public.orders(id) ON DELETE CASCADE;


--
-- Name: exclude_product_coupons exclude_product_coupons_coupon_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.exclude_product_coupons
    ADD CONSTRAINT exclude_product_coupons_coupon_id_foreign FOREIGN KEY (coupon_id) REFERENCES public.coupons(id) ON DELETE CASCADE;


--
-- Name: exclude_product_coupons exclude_product_coupons_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.exclude_product_coupons
    ADD CONSTRAINT exclude_product_coupons_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: faqs faqs_created_by_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.faqs
    ADD CONSTRAINT faqs_created_by_id_foreign FOREIGN KEY (created_by_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: feedbacks feedbacks_consumer_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.feedbacks
    ADD CONSTRAINT feedbacks_consumer_id_foreign FOREIGN KEY (consumer_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: feedbacks feedbacks_question_and_answer_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.feedbacks
    ADD CONSTRAINT feedbacks_question_and_answer_id_foreign FOREIGN KEY (question_and_answer_id) REFERENCES public.question_and_answers(id) ON DELETE CASCADE;


--
-- Name: model_has_permissions model_has_permissions_permission_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.model_has_permissions
    ADD CONSTRAINT model_has_permissions_permission_id_foreign FOREIGN KEY (permission_id) REFERENCES public.permissions(id) ON DELETE CASCADE;


--
-- Name: model_has_roles model_has_roles_role_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.model_has_roles
    ADD CONSTRAINT model_has_roles_role_id_foreign FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE CASCADE;


--
-- Name: module_permissions module_permissions_module_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.module_permissions
    ADD CONSTRAINT module_permissions_module_id_foreign FOREIGN KEY (module_id) REFERENCES public.modules(id) ON DELETE CASCADE;


--
-- Name: module_permissions module_permissions_permission_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.module_permissions
    ADD CONSTRAINT module_permissions_permission_id_foreign FOREIGN KEY (permission_id) REFERENCES public.permissions(id) ON DELETE CASCADE;


--
-- Name: order_notes order_notes_created_by_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_notes
    ADD CONSTRAINT order_notes_created_by_id_foreign FOREIGN KEY (created_by_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: order_notes order_notes_order_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_notes
    ADD CONSTRAINT order_notes_order_id_foreign FOREIGN KEY (order_id) REFERENCES public.orders(id) ON DELETE CASCADE;


--
-- Name: order_products order_products_order_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_products
    ADD CONSTRAINT order_products_order_id_foreign FOREIGN KEY (order_id) REFERENCES public.orders(id) ON DELETE CASCADE;


--
-- Name: order_products order_products_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_products
    ADD CONSTRAINT order_products_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: order_products order_products_variation_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_products
    ADD CONSTRAINT order_products_variation_id_foreign FOREIGN KEY (variation_id) REFERENCES public.variations(id) ON DELETE CASCADE;


--
-- Name: order_status order_status_created_by_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_status
    ADD CONSTRAINT order_status_created_by_id_foreign FOREIGN KEY (created_by_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: order_status_histories order_status_histories_new_status_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_status_histories
    ADD CONSTRAINT order_status_histories_new_status_id_foreign FOREIGN KEY (new_status_id) REFERENCES public.order_status(id) ON DELETE RESTRICT;


--
-- Name: order_status_histories order_status_histories_old_status_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_status_histories
    ADD CONSTRAINT order_status_histories_old_status_id_foreign FOREIGN KEY (old_status_id) REFERENCES public.order_status(id) ON DELETE SET NULL;


--
-- Name: order_status_histories order_status_histories_order_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_status_histories
    ADD CONSTRAINT order_status_histories_order_id_foreign FOREIGN KEY (order_id) REFERENCES public.orders(id) ON DELETE CASCADE;


--
-- Name: order_status_histories order_status_histories_updated_by_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_status_histories
    ADD CONSTRAINT order_status_histories_updated_by_id_foreign FOREIGN KEY (updated_by_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: order_transactions order_transactions_order_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_transactions
    ADD CONSTRAINT order_transactions_order_id_foreign FOREIGN KEY (order_id) REFERENCES public.orders(id) ON DELETE CASCADE;


--
-- Name: orders orders_billing_address_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orders
    ADD CONSTRAINT orders_billing_address_id_foreign FOREIGN KEY (billing_address_id) REFERENCES public.addresses(id) ON DELETE CASCADE;


--
-- Name: orders orders_consumer_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orders
    ADD CONSTRAINT orders_consumer_id_foreign FOREIGN KEY (consumer_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: orders orders_coupon_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orders
    ADD CONSTRAINT orders_coupon_id_foreign FOREIGN KEY (coupon_id) REFERENCES public.coupons(id) ON DELETE CASCADE;


--
-- Name: orders orders_created_by_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orders
    ADD CONSTRAINT orders_created_by_id_foreign FOREIGN KEY (created_by_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: orders orders_order_status_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orders
    ADD CONSTRAINT orders_order_status_id_foreign FOREIGN KEY (order_status_id) REFERENCES public.order_status(id) ON DELETE CASCADE;


--
-- Name: orders orders_parent_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orders
    ADD CONSTRAINT orders_parent_id_foreign FOREIGN KEY (parent_id) REFERENCES public.orders(id) ON DELETE CASCADE;


--
-- Name: orders orders_shipping_address_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orders
    ADD CONSTRAINT orders_shipping_address_id_foreign FOREIGN KEY (shipping_address_id) REFERENCES public.addresses(id) ON DELETE CASCADE;


--
-- Name: orders orders_store_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orders
    ADD CONSTRAINT orders_store_id_foreign FOREIGN KEY (store_id) REFERENCES public.stores(id) ON DELETE CASCADE;


--
-- Name: pages pages_created_by_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pages
    ADD CONSTRAINT pages_created_by_id_foreign FOREIGN KEY (created_by_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: pages pages_page_meta_image_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pages
    ADD CONSTRAINT pages_page_meta_image_id_foreign FOREIGN KEY (page_meta_image_id) REFERENCES public.attachments(id) ON DELETE CASCADE;


--
-- Name: payment_accounts payment_accounts_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_accounts
    ADD CONSTRAINT payment_accounts_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: points points_consumer_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.points
    ADD CONSTRAINT points_consumer_id_foreign FOREIGN KEY (consumer_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: product_attributes product_attributes_attribute_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_attributes
    ADD CONSTRAINT product_attributes_attribute_id_foreign FOREIGN KEY (attribute_id) REFERENCES public.attributes(id) ON DELETE CASCADE;


--
-- Name: product_attributes product_attributes_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_attributes
    ADD CONSTRAINT product_attributes_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: product_categories product_categories_category_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_categories
    ADD CONSTRAINT product_categories_category_id_foreign FOREIGN KEY (category_id) REFERENCES public.categories(id) ON DELETE CASCADE;


--
-- Name: product_categories product_categories_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_categories
    ADD CONSTRAINT product_categories_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: product_coupons product_coupons_coupon_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_coupons
    ADD CONSTRAINT product_coupons_coupon_id_foreign FOREIGN KEY (coupon_id) REFERENCES public.coupons(id) ON DELETE CASCADE;


--
-- Name: product_coupons product_coupons_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_coupons
    ADD CONSTRAINT product_coupons_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: product_images product_images_attachment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_images
    ADD CONSTRAINT product_images_attachment_id_foreign FOREIGN KEY (attachment_id) REFERENCES public.attachments(id) ON DELETE CASCADE;


--
-- Name: product_images product_images_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_images
    ADD CONSTRAINT product_images_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: product_tags product_tags_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_tags
    ADD CONSTRAINT product_tags_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: product_tags product_tags_tag_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_tags
    ADD CONSTRAINT product_tags_tag_id_foreign FOREIGN KEY (tag_id) REFERENCES public.tags(id) ON DELETE CASCADE;


--
-- Name: products products_created_by_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_created_by_id_foreign FOREIGN KEY (created_by_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: products products_product_meta_image_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_product_meta_image_id_foreign FOREIGN KEY (product_meta_image_id) REFERENCES public.attachments(id) ON DELETE CASCADE;


--
-- Name: products products_product_thumbnail_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_product_thumbnail_id_foreign FOREIGN KEY (product_thumbnail_id) REFERENCES public.attachments(id) ON DELETE CASCADE;


--
-- Name: products products_size_chart_image_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_size_chart_image_id_foreign FOREIGN KEY (size_chart_image_id) REFERENCES public.attachments(id) ON DELETE CASCADE;


--
-- Name: products products_store_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_store_id_foreign FOREIGN KEY (store_id) REFERENCES public.stores(id) ON DELETE CASCADE;


--
-- Name: products products_tax_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_tax_id_foreign FOREIGN KEY (tax_id) REFERENCES public.taxes(id) ON DELETE CASCADE;


--
-- Name: question_and_answers question_and_answers_consumer_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.question_and_answers
    ADD CONSTRAINT question_and_answers_consumer_id_foreign FOREIGN KEY (consumer_id) REFERENCES public.stores(id) ON DELETE CASCADE;


--
-- Name: question_and_answers question_and_answers_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.question_and_answers
    ADD CONSTRAINT question_and_answers_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: question_and_answers question_and_answers_store_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.question_and_answers
    ADD CONSTRAINT question_and_answers_store_id_foreign FOREIGN KEY (store_id) REFERENCES public.stores(id) ON DELETE CASCADE;


--
-- Name: refunds refunds_consumer_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.refunds
    ADD CONSTRAINT refunds_consumer_id_foreign FOREIGN KEY (consumer_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: refunds refunds_order_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.refunds
    ADD CONSTRAINT refunds_order_id_foreign FOREIGN KEY (order_id) REFERENCES public.orders(id) ON DELETE CASCADE;


--
-- Name: refunds refunds_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.refunds
    ADD CONSTRAINT refunds_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: refunds refunds_refund_image_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.refunds
    ADD CONSTRAINT refunds_refund_image_id_foreign FOREIGN KEY (refund_image_id) REFERENCES public.attachments(id) ON DELETE CASCADE;


--
-- Name: refunds refunds_store_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.refunds
    ADD CONSTRAINT refunds_store_id_foreign FOREIGN KEY (store_id) REFERENCES public.stores(id) ON DELETE CASCADE;


--
-- Name: refunds refunds_variation_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.refunds
    ADD CONSTRAINT refunds_variation_id_foreign FOREIGN KEY (variation_id) REFERENCES public.variations(id) ON DELETE CASCADE;


--
-- Name: related_products related_products_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.related_products
    ADD CONSTRAINT related_products_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: related_products related_products_related_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.related_products
    ADD CONSTRAINT related_products_related_product_id_foreign FOREIGN KEY (related_product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: returns returns_order_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.returns
    ADD CONSTRAINT returns_order_id_foreign FOREIGN KEY (order_id) REFERENCES public.orders(id) ON DELETE CASCADE;


--
-- Name: returns returns_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.returns
    ADD CONSTRAINT returns_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: returns returns_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.returns
    ADD CONSTRAINT returns_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: reviews reviews_consumer_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reviews
    ADD CONSTRAINT reviews_consumer_id_foreign FOREIGN KEY (consumer_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: reviews reviews_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reviews
    ADD CONSTRAINT reviews_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: reviews reviews_review_image_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reviews
    ADD CONSTRAINT reviews_review_image_id_foreign FOREIGN KEY (review_image_id) REFERENCES public.attachments(id) ON DELETE CASCADE;


--
-- Name: reviews reviews_store_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reviews
    ADD CONSTRAINT reviews_store_id_foreign FOREIGN KEY (store_id) REFERENCES public.stores(id) ON DELETE CASCADE;


--
-- Name: role_has_permissions role_has_permissions_permission_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_has_permissions
    ADD CONSTRAINT role_has_permissions_permission_id_foreign FOREIGN KEY (permission_id) REFERENCES public.permissions(id) ON DELETE CASCADE;


--
-- Name: role_has_permissions role_has_permissions_role_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_has_permissions
    ADD CONSTRAINT role_has_permissions_role_id_foreign FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE CASCADE;


--
-- Name: shipping_rules shipping_rules_created_by_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.shipping_rules
    ADD CONSTRAINT shipping_rules_created_by_id_foreign FOREIGN KEY (created_by_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: shippings shippings_country_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.shippings
    ADD CONSTRAINT shippings_country_id_foreign FOREIGN KEY (country_id) REFERENCES public.countries(id) ON DELETE CASCADE;


--
-- Name: shippings shippings_created_by_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.shippings
    ADD CONSTRAINT shippings_created_by_id_foreign FOREIGN KEY (created_by_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: states states_country_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.states
    ADD CONSTRAINT states_country_id_foreign FOREIGN KEY (country_id) REFERENCES public.countries(id) ON DELETE CASCADE;


--
-- Name: stores stores_country_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stores
    ADD CONSTRAINT stores_country_id_foreign FOREIGN KEY (country_id) REFERENCES public.countries(id) ON DELETE CASCADE;


--
-- Name: stores stores_created_by_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stores
    ADD CONSTRAINT stores_created_by_id_foreign FOREIGN KEY (created_by_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: stores stores_state_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stores
    ADD CONSTRAINT stores_state_id_foreign FOREIGN KEY (state_id) REFERENCES public.states(id) ON DELETE CASCADE;


--
-- Name: stores stores_store_cover_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stores
    ADD CONSTRAINT stores_store_cover_id_foreign FOREIGN KEY (store_cover_id) REFERENCES public.attachments(id) ON DELETE CASCADE;


--
-- Name: stores stores_store_logo_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stores
    ADD CONSTRAINT stores_store_logo_id_foreign FOREIGN KEY (store_logo_id) REFERENCES public.attachments(id) ON DELETE CASCADE;


--
-- Name: stores stores_vendor_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stores
    ADD CONSTRAINT stores_vendor_id_foreign FOREIGN KEY (vendor_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: tags tags_created_by_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tags
    ADD CONSTRAINT tags_created_by_id_foreign FOREIGN KEY (created_by_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: taxes taxes_created_by_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.taxes
    ADD CONSTRAINT taxes_created_by_id_foreign FOREIGN KEY (created_by_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: telescope_entries_tags telescope_entries_tags_entry_uuid_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.telescope_entries_tags
    ADD CONSTRAINT telescope_entries_tags_entry_uuid_foreign FOREIGN KEY (entry_uuid) REFERENCES public.telescope_entries(uuid) ON DELETE CASCADE;


--
-- Name: transactions transactions_from_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_from_foreign FOREIGN KEY ("from") REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: transactions transactions_order_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_order_id_foreign FOREIGN KEY (order_id) REFERENCES public.orders(id) ON DELETE CASCADE;


--
-- Name: transactions transactions_point_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_point_id_foreign FOREIGN KEY (point_id) REFERENCES public.points(id) ON DELETE CASCADE;


--
-- Name: transactions transactions_wallet_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_wallet_id_foreign FOREIGN KEY (wallet_id) REFERENCES public.wallets(id) ON DELETE CASCADE;


--
-- Name: users users_created_by_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_created_by_id_foreign FOREIGN KEY (created_by_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: variation_attribute_values variation_attribute_values_attribute_value_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.variation_attribute_values
    ADD CONSTRAINT variation_attribute_values_attribute_value_id_foreign FOREIGN KEY (attribute_value_id) REFERENCES public.attribute_values(id) ON DELETE CASCADE;


--
-- Name: variation_attribute_values variation_attribute_values_variation_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.variation_attribute_values
    ADD CONSTRAINT variation_attribute_values_variation_id_foreign FOREIGN KEY (variation_id) REFERENCES public.variations(id) ON DELETE CASCADE;


--
-- Name: variations variations_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.variations
    ADD CONSTRAINT variations_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: variations variations_variation_image_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.variations
    ADD CONSTRAINT variations_variation_image_id_foreign FOREIGN KEY (variation_image_id) REFERENCES public.attachments(id) ON DELETE CASCADE;


--
-- Name: vendor_transactions vendor_transactions_from_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vendor_transactions
    ADD CONSTRAINT vendor_transactions_from_foreign FOREIGN KEY ("from") REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: vendor_transactions vendor_transactions_vendor_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vendor_transactions
    ADD CONSTRAINT vendor_transactions_vendor_id_foreign FOREIGN KEY (vendor_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: vendor_transactions vendor_transactions_vendor_wallet_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vendor_transactions
    ADD CONSTRAINT vendor_transactions_vendor_wallet_id_foreign FOREIGN KEY (vendor_wallet_id) REFERENCES public.vendor_wallets(id) ON DELETE CASCADE;


--
-- Name: vendor_wallets vendor_wallets_vendor_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vendor_wallets
    ADD CONSTRAINT vendor_wallets_vendor_id_foreign FOREIGN KEY (vendor_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: wallets wallets_consumer_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.wallets
    ADD CONSTRAINT wallets_consumer_id_foreign FOREIGN KEY (consumer_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: wishlists wishlists_consumer_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.wishlists
    ADD CONSTRAINT wishlists_consumer_id_foreign FOREIGN KEY (consumer_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: wishlists wishlists_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.wishlists
    ADD CONSTRAINT wishlists_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: withdraw_requests withdraw_requests_vendor_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.withdraw_requests
    ADD CONSTRAINT withdraw_requests_vendor_id_foreign FOREIGN KEY (vendor_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: withdraw_requests withdraw_requests_vendor_wallet_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.withdraw_requests
    ADD CONSTRAINT withdraw_requests_vendor_wallet_id_foreign FOREIGN KEY (vendor_wallet_id) REFERENCES public.vendor_wallets(id) ON DELETE CASCADE;


--
-- Name: yoco_transactions yoco_transactions_order_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.yoco_transactions
    ADD CONSTRAINT yoco_transactions_order_id_foreign FOREIGN KEY (order_id) REFERENCES public.orders(id) ON DELETE SET NULL;


--
-- PostgreSQL database dump complete
--

\unrestrict HVQhcNUVkPPluWTfZ6URnFrpLcphwicM2aAOpLeiHI8R2enLaIQHqmvmfHDSFs5

--
-- PostgreSQL database dump
--

\restrict mfkVUbaip5OvdNXnzwuSy6D4O8wBMOrccpAnPP95kNolmawNQZQ5Hxi0la8oTdO

-- Dumped from database version 17.6
-- Dumped by pg_dump version 17.6

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.migrations (id, migration, batch) FROM stdin;
1	2026_02_08_000000_create_analytics_migrations_table	1
2	2026_02_08_000001_create_analytics_page_views_table	1
3	2026_02_08_000002_create_analytics_user_sessions_table	1
4	2026_02_08_000003_create_analytics_user_events_table	1
5	2026_02_08_000004_create_analytics_cart_abandonments_table	1
6	2026_03_25_000001_add_is_bot_to_analytics_tables	2
82	0001_01_01_000000_create_users_table	3
83	0001_01_01_000001_create_cache_table	3
84	0001_01_01_000002_create_jobs_table	3
85	2014_11_29_1000004_create_attachment_table	3
86	2018_08_08_100000_create_telescope_entries_table	3
87	2021_11_25_094447_create_countries_table	3
88	2021_11_25_120136_create_taxes_table	3
89	2022_09_23_090305_create_attributes_table	3
90	2022_09_28_105314_create_categories_table	3
91	2022_10_01_135505_create_tags_table	3
92	2022_10_17_035823_create_themes_table	3
93	2022_11_09_072500_create_stores_table	3
94	2022_11_12_053826_create_products_table	3
95	2022_11_17_111446_create_blogs_table	3
96	2022_11_30_040956_create_shippings_table	3
97	2022_12_03_041404_create_coupons_table	3
98	2022_12_08_092552_create_settings_table	3
99	2023_01_13_060558_create_addresses_table	3
100	2023_01_24_084530_create_orders_table	3
101	2023_02_01_035655_create_wallets_table	3
102	2023_02_10_053509_create_theme_options_table	3
103	2023_02_16_132426_create_currencies_table	3
104	2023_02_17_043333_create_pages_table	3
105	2023_02_24_054056_create_home_pages_table	3
106	2023_03_01_050232_create_wishlists_table	3
107	2023_03_01_100808_create_carts_table	3
108	2023_03_02_033848_create_compares_table	3
109	2023_04_05_042121_create_vendor_wallets_table	3
110	2023_04_05_062421_create_commission_histories_table	3
111	2023_04_05_062439_create_withdraw_requests_table	3
112	2023_04_06_051415_create_payment_accounts_table	3
113	2023_04_06_133831_create_vendor_transactions_table	3
114	2023_04_11_120059_create_faqs_table	3
115	2023_04_14_110653_create_reviews_table	3
116	2023_04_20_044705_create_notifications_table	3
117	2023_04_24_050852_create_refunds_table	3
118	2023_04_24_050852_create_seeders_table	3
119	2023_09_12_045133_create_question_and_answers_table	3
120	2025_01_07_090631_create_personal_access_tokens_table	3
121	2025_01_07_093454_create_permission_tables	3
122	2025_01_07_101611_create_media_table	3
123	2025_01_26_102503_add_indexes_to_tables	3
124	2025_02_13_112911_add_created_at_index_to_reviews_table	3
125	2025_02_15_153807_add_takealot_url_column_to_attachements_table	3
126	2025_02_19_141506_add_media_id_to_attachments_table	3
127	2025_03_19_104113_add_column_search_keywords_on_products_table	3
128	2025_03_19_113052_add_search_tsv_to_products	3
129	2025_03_19_121439_add_pg_trgm_extension_and_trigram_index_to_products	3
130	2025_03_23_175211_add_category_icon_and_image_uuid_column_to_categories_table	3
131	2025_04_26_161805_add_column_delivery_price_to_orders_table	3
132	2025_07_16_000000_create_pesepay_transactions_table	3
133	2025_07_18_000000_create_payfast_transactions_table	3
134	2025_09_05_000001_add_note_to_orders_table	3
135	2025_09_07_000001_create_order_status_histories_table	3
136	2025_09_14_000001_add_currency_fields_to_orders_table	3
137	2025_09_14_000002_add_exchange_rate_to_orders_table	3
138	2025_09_22_000000_create_dpo_zambia_transactions_table	3
139	2025_09_22_000001_create_payfast_transactions_table	3
140	2025_09_22_000002_create_pesepay_transactions_table	3
141	2025_09_24_000001_create_order_notes_table	3
142	2025_10_06_000000_create_yoco_transactions_table	3
143	2025_10_06_000001_add_order_number_to_yoco_transactions_table	3
144	2025_10_07_090508_create_returns_table	3
145	2025_10_07_133651_add_preferred_outcome_to_returns_table	3
146	2025_10_07_141209_add_unique_index_on_returns_user_order_product	3
147	2025_10_11_120000_add_expedited_shipping_to_products_and_carts	3
148	2025_10_11_140000_add_fast_shipping_total_to_orders	3
149	2025_10_12_000001_add_rejection_reason_to_returns_table	3
150	2025_10_15_000001_add_fast_shipping_to_order_products	3
151	2025_10_18_000001_change_shipping_days_to_string	3
152	2025_10_18_055234_create_modules_table	3
153	2025_10_18_120000_add_item_status_to_order_products	3
154	2025_10_20_000001_add_eta_to_order_products	3
155	2025_11_09_000001_add_selected_attributes_to_order_products	3
156	2025_11_12_000001_add_currency_preference_to_users_table	3
\.


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.migrations_id_seq', 156, true);


--
-- PostgreSQL database dump complete
--

\unrestrict mfkVUbaip5OvdNXnzwuSy6D4O8wBMOrccpAnPP95kNolmawNQZQ5Hxi0la8oTdO

