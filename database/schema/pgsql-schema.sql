--
-- PostgreSQL database dump
--

\restrict v5WFgBaAHsI7gvrob6xF7MHugWOjgKwGpWgO9D0HYW0fvRbJ2ISgSRsirnRgIXo

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
    ip_address inet,
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
-- Name: cash_book_categories; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cash_book_categories (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    slug character varying(255) NOT NULL,
    type character varying(255) DEFAULT 'both'::character varying NOT NULL,
    color character varying(255),
    description text,
    is_active boolean DEFAULT true NOT NULL,
    deleted_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT cash_book_categories_type_check CHECK (((type)::text = ANY (ARRAY[('income'::character varying)::text, ('expense'::character varying)::text, ('both'::character varying)::text])))
);


--
-- Name: cash_book_categories_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.cash_book_categories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: cash_book_categories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.cash_book_categories_id_seq OWNED BY public.cash_book_categories.id;


--
-- Name: cash_book_entries; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cash_book_entries (
    id bigint NOT NULL,
    entry_date date NOT NULL,
    entry_time time(0) without time zone,
    remark text,
    party character varying(255),
    category_id bigint,
    mode character varying(255) DEFAULT 'cash'::character varying NOT NULL,
    entered_by bigint,
    cash_in numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    cash_out numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    balance numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    reference_number character varying(255),
    reference_type character varying(255),
    reference_id bigint,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    branch character varying(255) DEFAULT 'Harare'::character varying NOT NULL,
    CONSTRAINT cash_book_entries_branch_check CHECK (((branch)::text = ANY (ARRAY[('Harare'::character varying)::text, ('Bulawayo'::character varying)::text, ('Mutare'::character varying)::text, ('Zambia'::character varying)::text]))),
    CONSTRAINT cash_book_entries_mode_check CHECK (((mode)::text = ANY (ARRAY[('cash'::character varying)::text, ('bank'::character varying)::text, ('card'::character varying)::text, ('mobile_money'::character varying)::text, ('other'::character varying)::text])))
);


--
-- Name: cash_book_entries_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.cash_book_entries_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: cash_book_entries_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.cash_book_entries_id_seq OWNED BY public.cash_book_entries.id;


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
    category_icon_uuid uuid,
    sort_order integer DEFAULT 0 NOT NULL
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
-- Name: commission_history_items; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.commission_history_items (
    id bigint NOT NULL,
    commission_history_id bigint NOT NULL,
    product_id bigint NOT NULL,
    product_name character varying(255) NOT NULL,
    product_sku character varying(255),
    product_price numeric(10,2) NOT NULL,
    quantity integer DEFAULT 1 NOT NULL,
    subtotal numeric(10,2) NOT NULL,
    commission_rate numeric(5,2) NOT NULL,
    commission_source character varying(255) DEFAULT 'category'::character varying NOT NULL,
    category_id bigint,
    category_name character varying(255),
    admin_commission numeric(10,2) NOT NULL,
    vendor_commission numeric(10,2) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: COLUMN commission_history_items.commission_rate; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.commission_history_items.commission_rate IS 'Commission percentage applied (e.g., 15.00 for 15%)';


--
-- Name: COLUMN commission_history_items.commission_source; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.commission_history_items.commission_source IS 'category, default, or manual';


--
-- Name: commission_history_items_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.commission_history_items_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: commission_history_items_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.commission_history_items_id_seq OWNED BY public.commission_history_items.id;


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
    CONSTRAINT coupons_type_check CHECK (((type)::text = ANY (ARRAY[('fixed'::character varying)::text, ('free_shipping'::character varying)::text, ('percentage'::character varying)::text])))
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
    CONSTRAINT currencies_decimal_separator_check CHECK (((decimal_separator)::text = ANY (ARRAY[('comma'::character varying)::text, ('period'::character varying)::text, ('space'::character varying)::text]))),
    CONSTRAINT currencies_symbol_position_check CHECK (((symbol_position)::text = ANY (ARRAY[('before_price'::character varying)::text, ('after_price'::character varying)::text]))),
    CONSTRAINT currencies_thousands_separator_check CHECK (((thousands_separator)::text = ANY (ARRAY[('comma'::character varying)::text, ('period'::character varying)::text, ('space'::character varying)::text])))
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
    CONSTRAINT feedbacks_reaction_check CHECK (((reaction)::text = ANY (ARRAY[('liked'::character varying)::text, ('disliked'::character varying)::text])))
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
-- Name: import_jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.import_jobs (
    id bigint NOT NULL,
    batch_id character varying(100) NOT NULL,
    filename character varying(255) NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    error_message text,
    total_items integer DEFAULT 0 NOT NULL,
    processed_items integer DEFAULT 0 NOT NULL,
    updated_items integer DEFAULT 0 NOT NULL,
    skipped_items integer DEFAULT 0 NOT NULL,
    failed_items integer DEFAULT 0 NOT NULL,
    skip_reasons json,
    percentage numeric(8,4),
    todays_rate numeric(12,6),
    started_at timestamp(0) without time zone,
    completed_at timestamp(0) without time zone,
    duration_seconds integer,
    user_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    import_type character varying(50) DEFAULT 'fast-import'::character varying NOT NULL,
    CONSTRAINT import_jobs_status_check CHECK (((status)::text = ANY (ARRAY[('pending'::character varying)::text, ('processing'::character varying)::text, ('completed'::character varying)::text, ('failed'::character varying)::text])))
);


--
-- Name: import_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.import_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: import_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.import_jobs_id_seq OWNED BY public.import_jobs.id;


--
-- Name: inventory_receiving_temp; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.inventory_receiving_temp (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    shipment_id bigint NOT NULL,
    order_number character varying(255),
    product_name character varying(255) NOT NULL,
    quantity integer DEFAULT 1 NOT NULL,
    destination character varying(255),
    qr_data json,
    scanned_at timestamp(0) without time zone NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: inventory_receiving_temp_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.inventory_receiving_temp_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: inventory_receiving_temp_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.inventory_receiving_temp_id_seq OWNED BY public.inventory_receiving_temp.id;


--
-- Name: inventory_shipment_history; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.inventory_shipment_history (
    id bigint NOT NULL,
    shipment_id bigint NOT NULL,
    user_id bigint,
    action character varying(255) NOT NULL,
    changes text,
    old_values text,
    new_values text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: COLUMN inventory_shipment_history.action; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.inventory_shipment_history.action IS 'created, updated, deleted, restored';


--
-- Name: COLUMN inventory_shipment_history.changes; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.inventory_shipment_history.changes IS 'JSON of what changed';


--
-- Name: COLUMN inventory_shipment_history.old_values; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.inventory_shipment_history.old_values IS 'JSON of old values';


--
-- Name: COLUMN inventory_shipment_history.new_values; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.inventory_shipment_history.new_values IS 'JSON of new values';


--
-- Name: inventory_shipment_history_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.inventory_shipment_history_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: inventory_shipment_history_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.inventory_shipment_history_id_seq OWNED BY public.inventory_shipment_history.id;


--
-- Name: inventory_shipments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.inventory_shipments (
    id bigint NOT NULL,
    "order" integer,
    title character varying(255) NOT NULL,
    quantity integer NOT NULL,
    destination character varying(255),
    status character varying(255),
    transporter character varying(255),
    date date,
    eta date,
    f_status character varying(255),
    received_by bigint,
    notes text,
    created_by bigint,
    updated_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    signed_by bigint,
    srs character varying(100)
);


--
-- Name: COLUMN inventory_shipments."order"; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.inventory_shipments."order" IS 'Display order/priority';


--
-- Name: COLUMN inventory_shipments.date; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.inventory_shipments.date IS 'Shipment date';


--
-- Name: COLUMN inventory_shipments.eta; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.inventory_shipments.eta IS 'Estimated time of arrival';


--
-- Name: COLUMN inventory_shipments.f_status; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.inventory_shipments.f_status IS 'Final/Freight Status';


--
-- Name: inventory_shipments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.inventory_shipments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: inventory_shipments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.inventory_shipments_id_seq OWNED BY public.inventory_shipments.id;


--
-- Name: invoice_quotation_histories; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.invoice_quotation_histories (
    id bigint NOT NULL,
    invoice_quotation_id bigint NOT NULL,
    user_id bigint,
    action character varying(255) NOT NULL,
    field_name character varying(255),
    old_value text,
    new_value text,
    description text NOT NULL,
    metadata json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: invoice_quotation_histories_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.invoice_quotation_histories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: invoice_quotation_histories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.invoice_quotation_histories_id_seq OWNED BY public.invoice_quotation_histories.id;


--
-- Name: invoice_quotation_items; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.invoice_quotation_items (
    id bigint NOT NULL,
    invoice_quotation_id bigint NOT NULL,
    product_id bigint,
    variation_id bigint,
    product_name character varying(255) NOT NULL,
    sku character varying(255),
    description text,
    image_url character varying(255),
    quantity numeric(10,2) NOT NULL,
    unit_price numeric(15,2) NOT NULL,
    subtotal numeric(15,2) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: invoice_quotation_items_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.invoice_quotation_items_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: invoice_quotation_items_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.invoice_quotation_items_id_seq OWNED BY public.invoice_quotation_items.id;


--
-- Name: invoices_quotations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.invoices_quotations (
    id bigint NOT NULL,
    document_number character varying(255) NOT NULL,
    document_type character varying(255) NOT NULL,
    currency_code character varying(3) NOT NULL,
    customer_name character varying(255) NOT NULL,
    customer_email character varying(255),
    customer_phone character varying(255),
    customer_address text,
    user_id bigint,
    subtotal numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    discount_amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    discount_type character varying(255) DEFAULT 'amount'::character varying NOT NULL,
    discount_value numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    vat_amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    vat_percentage numeric(5,2) DEFAULT '15'::numeric NOT NULL,
    include_vat boolean DEFAULT true NOT NULL,
    total_amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    notes text,
    terms_conditions text,
    issue_date date NOT NULL,
    due_date date,
    valid_until date,
    status character varying(50) DEFAULT 'draft'::character varying NOT NULL,
    created_by bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    shipping_total numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    delivery_method character varying(255),
    delivery_description text,
    delivery_price numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    delivery_interval character varying(255),
    collection_point character varying(255),
    CONSTRAINT invoices_quotations_discount_type_check CHECK (((discount_type)::text = ANY (ARRAY[('percentage'::character varying)::text, ('amount'::character varying)::text]))),
    CONSTRAINT invoices_quotations_document_type_check CHECK (((document_type)::text = ANY (ARRAY[('invoice'::character varying)::text, ('quotation'::character varying)::text, ('receipt'::character varying)::text, ('proforma'::character varying)::text, ('delivery_note'::character varying)::text]))),
    CONSTRAINT invoices_quotations_status_check CHECK (((status)::text = ANY ((ARRAY['autosave'::character varying, 'draft'::character varying, 'sent'::character varying, 'paid'::character varying, 'cancelled'::character varying, 'expired'::character varying])::text[])))
);


--
-- Name: invoices_quotations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.invoices_quotations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: invoices_quotations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.invoices_quotations_id_seq OWNED BY public.invoices_quotations.id;


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
-- Name: layby_applications; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.layby_applications (
    id bigint NOT NULL,
    application_number character varying(255) NOT NULL,
    user_id bigint NOT NULL,
    product_id bigint NOT NULL,
    variation_id bigint,
    selected_attribute_ids json,
    variation_display_name character varying(255),
    product_name character varying(255) NOT NULL,
    product_price numeric(10,2) NOT NULL,
    currency character varying(10) DEFAULT 'USD'::character varying NOT NULL,
    currency_symbol character varying(10) DEFAULT '$'::character varying NOT NULL,
    exchange_rate numeric(10,4) DEFAULT '1'::numeric NOT NULL,
    duration_months integer DEFAULT 3 NOT NULL,
    deposit_amount numeric(10,2) NOT NULL,
    monthly_amount numeric(10,2) NOT NULL,
    total_amount numeric(10,2) NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    rejection_reason text,
    approved_at timestamp(0) without time zone,
    rejected_at timestamp(0) without time zone,
    approved_by bigint,
    total_paid numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    balance_remaining numeric(10,2) NOT NULL,
    last_payment_at timestamp(0) without time zone,
    completed_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    id_document_path character varying(255),
    id_document_type character varying(255),
    id_document_number character varying(255),
    order_id bigint,
    id_document_attachment_id bigint,
    cancellation_reason text,
    cancelled_at timestamp(0) without time zone,
    cancelled_by bigint,
    CONSTRAINT layby_applications_status_check CHECK (((status)::text = ANY (ARRAY[('pending'::character varying)::text, ('approved'::character varying)::text, ('rejected'::character varying)::text, ('active'::character varying)::text, ('completed'::character varying)::text, ('cancelled'::character varying)::text])))
);


--
-- Name: layby_applications_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.layby_applications_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: layby_applications_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.layby_applications_id_seq OWNED BY public.layby_applications.id;


--
-- Name: layby_payments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.layby_payments (
    id bigint NOT NULL,
    layby_application_id bigint NOT NULL,
    payment_number character varying(255) NOT NULL,
    amount numeric(10,2) NOT NULL,
    currency character varying(10) DEFAULT 'USD'::character varying NOT NULL,
    payment_method character varying(255),
    transaction_id character varying(255),
    payment_status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    payment_note text,
    payment_meta json,
    paid_at timestamp(0) without time zone,
    captured_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    currency_symbol character varying(10),
    exchange_rate numeric(10,4) DEFAULT '1'::numeric NOT NULL,
    gateway_reference character varying(255),
    CONSTRAINT layby_payments_payment_status_check CHECK (((payment_status)::text = ANY (ARRAY[('pending'::character varying)::text, ('completed'::character varying)::text, ('failed'::character varying)::text, ('refunded'::character varying)::text])))
);


--
-- Name: layby_payments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.layby_payments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: layby_payments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.layby_payments_id_seq OWNED BY public.layby_payments.id;


--
-- Name: layby_settings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.layby_settings (
    id bigint NOT NULL,
    key character varying(255) NOT NULL,
    value text,
    description text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: layby_settings_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.layby_settings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: layby_settings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.layby_settings_id_seq OWNED BY public.layby_settings.id;


--
-- Name: marketing_feedback; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.marketing_feedback (
    id bigint NOT NULL,
    user_id bigint,
    order_number character varying(255),
    order_id bigint,
    ordering_process_rating character varying(255) NOT NULL,
    heard_about_source character varying(255) NOT NULL,
    heard_about_other character varying(255),
    user_name character varying(255),
    user_email character varying(255),
    user_phone character varying(255),
    additional_comments text,
    ip_address character varying(255),
    user_agent character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    country_code character varying(2),
    country_name character varying(255),
    CONSTRAINT marketing_feedback_ordering_process_rating_check CHECK (((ordering_process_rating)::text = ANY (ARRAY[('excellent'::character varying)::text, ('good'::character varying)::text, ('fair'::character varying)::text, ('poor'::character varying)::text])))
);


--
-- Name: marketing_feedback_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.marketing_feedback_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: marketing_feedback_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.marketing_feedback_id_seq OWNED BY public.marketing_feedback.id;


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
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
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
    CONSTRAINT order_notes_privacy_check CHECK (((privacy)::text = ANY (ARRAY[('public'::character varying)::text, ('private'::character varying)::text])))
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
    product_id bigint,
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
    variation_display_name character varying(255),
    added_to_inventory boolean DEFAULT false NOT NULL,
    inventory_shipment_id bigint,
    added_to_inventory_at timestamp(0) without time zone,
    qr_code text,
    estimated_delivery_text character varying(255)
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
-- Name: order_reminder_emails; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.order_reminder_emails (
    id bigint NOT NULL,
    order_id bigint NOT NULL,
    order_number character varying(255) NOT NULL,
    user_id bigint NOT NULL,
    email character varying(255) NOT NULL,
    reminder_type character varying(255) NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    error_message text,
    sent_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT order_reminder_emails_reminder_type_check CHECK (((reminder_type)::text = ANY (ARRAY[('first'::character varying)::text, ('second'::character varying)::text, ('cancellation'::character varying)::text]))),
    CONSTRAINT order_reminder_emails_status_check CHECK (((status)::text = ANY (ARRAY[('pending'::character varying)::text, ('sent'::character varying)::text, ('failed'::character varying)::text])))
);


--
-- Name: COLUMN order_reminder_emails.reminder_type; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.order_reminder_emails.reminder_type IS 'Type of reminder email sent';


--
-- Name: order_reminder_emails_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.order_reminder_emails_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: order_reminder_emails_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.order_reminder_emails_id_seq OWNED BY public.order_reminder_emails.id;


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
    fast_shipping_total numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    feedback_token character varying(64),
    feedback_token_expires_at timestamp(0) without time zone,
    is_gift_order boolean DEFAULT false NOT NULL,
    qr_code_url character varying(500)
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
    ip_address inet,
    user_agent character varying(500),
    device_type character varying(50),
    browser character varying(100),
    os character varying(100),
    country character varying(100),
    city character varying(100),
    duration integer,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
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
    updated_at timestamp(0) without time zone,
    order_id character varying(255),
    gateway_transaction_id character varying(255),
    status character varying(255),
    raw_response json,
    other_fields json,
    currency character varying(10)
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
-- Name: product_brands; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.product_brands (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    slug character varying(255) NOT NULL,
    description text,
    brand_image_id bigint,
    meta_title character varying(255),
    meta_description text,
    status integer DEFAULT 1 NOT NULL,
    deleted_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: product_brands_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.product_brands_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: product_brands_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.product_brands_id_seq OWNED BY public.product_brands.id;


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
    original_currency_code character varying(10),
    original_price numeric(10,2),
    original_sale_price numeric(10,2),
    brand_id bigint,
    is_gift_card boolean DEFAULT false NOT NULL,
    voucher_validity_days integer,
    specifications text,
    warranty text,
    CONSTRAINT products_stock_status_check CHECK (((stock_status)::text = ANY (ARRAY[('in_stock'::character varying)::text, ('out_of_stock'::character varying)::text]))),
    CONSTRAINT products_type_check CHECK (((type)::text = ANY (ARRAY[('simple'::character varying)::text, ('classified'::character varying)::text])))
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
-- Name: promo_templates; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.promo_templates (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    html_content text NOT NULL,
    preview_image character varying(255),
    status integer DEFAULT 1 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: promo_templates_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.promo_templates_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: promo_templates_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.promo_templates_id_seq OWNED BY public.promo_templates.id;


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
    CONSTRAINT refunds_payment_type_check CHECK (((payment_type)::text = ANY (ARRAY[('wallet'::character varying)::text, ('paypal'::character varying)::text, ('bank'::character varying)::text]))),
    CONSTRAINT refunds_status_check CHECK (((status)::text = ANY (ARRAY[('pending'::character varying)::text, ('approved'::character varying)::text, ('rejected'::character varying)::text])))
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
-- Name: search_queries; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.search_queries (
    id bigint NOT NULL,
    query character varying(255) NOT NULL,
    normalized_query character varying(255),
    results_count integer DEFAULT 0 NOT NULL,
    user_id bigint,
    session_id character varying(255),
    ip_address character varying(255),
    user_agent character varying(255),
    filters json,
    sort_by character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: search_queries_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.search_queries_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: search_queries_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.search_queries_id_seq OWNED BY public.search_queries.id;


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
    CONSTRAINT shipping_rules_rule_type_check CHECK (((rule_type)::text = ANY (ARRAY[('base_on_price'::character varying)::text, ('base_on_weight'::character varying)::text]))),
    CONSTRAINT shipping_rules_shipping_type_check CHECK (((shipping_type)::text = ANY (ARRAY[('free'::character varying)::text, ('fixed'::character varying)::text, ('percentage'::character varying)::text])))
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
    deleted_at timestamp(0) without time zone,
    is_vat_registered character varying(255),
    vat_number character varying(255),
    identification_type character varying(255),
    id_number character varying(255),
    legal_name character varying(255),
    trading_name character varying(255),
    monthly_revenue character varying(255),
    has_physical_stores character varying(255),
    number_of_stores integer,
    is_supplier_to_retailers character varying(255),
    has_marketplace_accounts character varying(255),
    number_of_products integer,
    primary_category character varying(255),
    stock_holding character varying(255),
    product_source character varying(255),
    product_branding character varying(255),
    owned_brands text,
    reseller_brands text,
    website character varying(255),
    social_media_page character varying(255),
    product_catalog_id bigint,
    business_summary text,
    product_uniqueness text,
    intended_products text,
    certifications character varying(255),
    referral_source character varying(255),
    is_banned boolean DEFAULT false NOT NULL,
    ban_reason text,
    banned_at timestamp(0) without time zone,
    banned_by bigint,
    CONSTRAINT stores_has_marketplace_accounts_check CHECK (((has_marketplace_accounts)::text = ANY (ARRAY[('yes'::character varying)::text, ('no'::character varying)::text]))),
    CONSTRAINT stores_has_physical_stores_check CHECK (((has_physical_stores)::text = ANY (ARRAY[('yes'::character varying)::text, ('no'::character varying)::text]))),
    CONSTRAINT stores_identification_type_check CHECK (((identification_type)::text = ANY (ARRAY[('id'::character varying)::text, ('passport'::character varying)::text]))),
    CONSTRAINT stores_is_supplier_to_retailers_check CHECK (((is_supplier_to_retailers)::text = ANY (ARRAY[('yes'::character varying)::text, ('no'::character varying)::text]))),
    CONSTRAINT stores_is_vat_registered_check CHECK (((is_vat_registered)::text = ANY (ARRAY[('yes'::character varying)::text, ('no'::character varying)::text])))
);


--
-- Name: COLUMN stores.is_banned; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.stores.is_banned IS 'Whether vendor is banned';


--
-- Name: COLUMN stores.ban_reason; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.stores.ban_reason IS 'Reason for banning vendor';


--
-- Name: COLUMN stores.banned_at; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.stores.banned_at IS 'When vendor was banned';


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
-- Name: system_tickets; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.system_tickets (
    id bigint NOT NULL,
    ticket_number character varying(255) NOT NULL,
    title character varying(255) NOT NULL,
    description text NOT NULL,
    priority character varying(255) DEFAULT 'medium'::character varying NOT NULL,
    status character varying(255) DEFAULT 'open'::character varying NOT NULL,
    category character varying(255),
    attachments json,
    created_by bigint NOT NULL,
    assigned_to bigint,
    closed_by bigint,
    closed_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    CONSTRAINT system_tickets_priority_check CHECK (((priority)::text = ANY (ARRAY[('low'::character varying)::text, ('medium'::character varying)::text, ('high'::character varying)::text, ('critical'::character varying)::text]))),
    CONSTRAINT system_tickets_status_check CHECK (((status)::text = ANY (ARRAY[('open'::character varying)::text, ('in_progress'::character varying)::text, ('testing'::character varying)::text, ('closed'::character varying)::text, ('reopened'::character varying)::text])))
);


--
-- Name: system_tickets_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.system_tickets_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: system_tickets_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.system_tickets_id_seq OWNED BY public.system_tickets.id;


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
-- Name: ticket_activities; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ticket_activities (
    id bigint NOT NULL,
    ticket_id bigint NOT NULL,
    user_id bigint NOT NULL,
    action character varying(255) DEFAULT 'updated'::character varying NOT NULL,
    comment text,
    attachments json,
    old_value character varying(255),
    new_value character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT ticket_activities_action_check CHECK (((action)::text = ANY (ARRAY[('created'::character varying)::text, ('updated'::character varying)::text, ('commented'::character varying)::text, ('status_changed'::character varying)::text, ('assigned'::character varying)::text, ('closed'::character varying)::text, ('reopened'::character varying)::text])))
);


--
-- Name: ticket_activities_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.ticket_activities_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: ticket_activities_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.ticket_activities_id_seq OWNED BY public.ticket_activities.id;


--
-- Name: ticket_messages; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ticket_messages (
    id bigint NOT NULL,
    ticket_id bigint NOT NULL,
    user_id bigint NOT NULL,
    message text NOT NULL,
    is_internal boolean DEFAULT false NOT NULL,
    attachments json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: ticket_messages_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.ticket_messages_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: ticket_messages_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.ticket_messages_id_seq OWNED BY public.ticket_messages.id;


--
-- Name: tickets; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tickets (
    id bigint NOT NULL,
    ticket_number character varying(255) NOT NULL,
    user_id bigint NOT NULL,
    subject character varying(255) NOT NULL,
    description text NOT NULL,
    priority character varying(255) DEFAULT 'medium'::character varying NOT NULL,
    status character varying(255) DEFAULT 'open'::character varying NOT NULL,
    category character varying(255) DEFAULT 'general'::character varying NOT NULL,
    assigned_to bigint,
    resolved_at timestamp(0) without time zone,
    closed_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    CONSTRAINT tickets_category_check CHECK (((category)::text = ANY (ARRAY[('general'::character varying)::text, ('technical'::character varying)::text, ('billing'::character varying)::text, ('account'::character varying)::text, ('order'::character varying)::text, ('other'::character varying)::text]))),
    CONSTRAINT tickets_priority_check CHECK (((priority)::text = ANY (ARRAY[('low'::character varying)::text, ('medium'::character varying)::text, ('high'::character varying)::text, ('urgent'::character varying)::text]))),
    CONSTRAINT tickets_status_check CHECK (((status)::text = ANY (ARRAY[('open'::character varying)::text, ('in_progress'::character varying)::text, ('waiting_customer'::character varying)::text, ('waiting_admin'::character varying)::text, ('resolved'::character varying)::text, ('closed'::character varying)::text])))
);


--
-- Name: tickets_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.tickets_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: tickets_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.tickets_id_seq OWNED BY public.tickets.id;


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
    CONSTRAINT transactions_type_check CHECK (((type)::text = ANY (ARRAY[('credit'::character varying)::text, ('debit'::character varying)::text])))
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
    updated_at timestamp(0) without time zone
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
    ip_address inet,
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
    updated_at timestamp(0) without time zone
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
    currency_exchange_rate numeric(10,4) DEFAULT '1'::numeric NOT NULL,
    branch character varying(255) DEFAULT 'None'::character varying NOT NULL,
    membership_card_number character varying(255),
    card_assigned_at timestamp(0) without time zone
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
    CONSTRAINT variations_stock_status_check CHECK (((stock_status)::text = ANY (ARRAY[('in_stock'::character varying)::text, ('out_of_stock'::character varying)::text, ('coming_soon'::character varying)::text])))
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
    CONSTRAINT vendor_transactions_type_check CHECK (((type)::text = ANY (ARRAY[('credit'::character varying)::text, ('debit'::character varying)::text])))
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
-- Name: vouchers; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.vouchers (
    id bigint NOT NULL,
    code character varying(50) NOT NULL,
    amount numeric(10,2) NOT NULL,
    currency_code character varying(3) DEFAULT 'USD'::character varying NOT NULL,
    product_id bigint,
    order_id bigint,
    purchased_by bigint,
    redeemed_by bigint,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    redeemed_at timestamp(0) without time zone,
    expires_at timestamp(0) without time zone,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT vouchers_status_check CHECK (((status)::text = ANY (ARRAY[('active'::character varying)::text, ('redeemed'::character varying)::text, ('expired'::character varying)::text, ('cancelled'::character varying)::text])))
);


--
-- Name: vouchers_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.vouchers_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: vouchers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.vouchers_id_seq OWNED BY public.vouchers.id;


--
-- Name: wallets; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.wallets (
    id bigint NOT NULL,
    consumer_id bigint,
    balance numeric(8,2) DEFAULT '0'::numeric NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    non_cashable_balance numeric(10,2) DEFAULT '0'::numeric NOT NULL
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
    payment_type character varying(50) DEFAULT 'bank'::character varying,
    is_used integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    approved_at timestamp(0) without time zone,
    approved_by bigint,
    rejected_at timestamp(0) without time zone,
    rejected_by bigint,
    payment_reference character varying(255),
    admin_notes text,
    rejection_reason text,
    payment_details json,
    CONSTRAINT withdraw_requests_payment_type_check CHECK (((payment_type)::text = ANY (ARRAY[('paypal'::character varying)::text, ('bank'::character varying)::text, ('Bank'::character varying)::text, ('Mobile Money'::character varying)::text, ('Wallet'::character varying)::text]))),
    CONSTRAINT withdraw_requests_status_check CHECK (((status)::text = ANY (ARRAY[('pending'::character varying)::text, ('approved'::character varying)::text, ('rejected'::character varying)::text])))
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
-- Name: cash_book_categories id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cash_book_categories ALTER COLUMN id SET DEFAULT nextval('public.cash_book_categories_id_seq'::regclass);


--
-- Name: cash_book_entries id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cash_book_entries ALTER COLUMN id SET DEFAULT nextval('public.cash_book_entries_id_seq'::regclass);


--
-- Name: categories id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categories ALTER COLUMN id SET DEFAULT nextval('public.categories_id_seq'::regclass);


--
-- Name: commission_histories id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.commission_histories ALTER COLUMN id SET DEFAULT nextval('public.commission_histories_id_seq'::regclass);


--
-- Name: commission_history_items id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.commission_history_items ALTER COLUMN id SET DEFAULT nextval('public.commission_history_items_id_seq'::regclass);


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
-- Name: import_jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.import_jobs ALTER COLUMN id SET DEFAULT nextval('public.import_jobs_id_seq'::regclass);


--
-- Name: inventory_receiving_temp id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory_receiving_temp ALTER COLUMN id SET DEFAULT nextval('public.inventory_receiving_temp_id_seq'::regclass);


--
-- Name: inventory_shipment_history id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory_shipment_history ALTER COLUMN id SET DEFAULT nextval('public.inventory_shipment_history_id_seq'::regclass);


--
-- Name: inventory_shipments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory_shipments ALTER COLUMN id SET DEFAULT nextval('public.inventory_shipments_id_seq'::regclass);


--
-- Name: invoice_quotation_histories id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoice_quotation_histories ALTER COLUMN id SET DEFAULT nextval('public.invoice_quotation_histories_id_seq'::regclass);


--
-- Name: invoice_quotation_items id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoice_quotation_items ALTER COLUMN id SET DEFAULT nextval('public.invoice_quotation_items_id_seq'::regclass);


--
-- Name: invoices_quotations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoices_quotations ALTER COLUMN id SET DEFAULT nextval('public.invoices_quotations_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: layby_applications id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.layby_applications ALTER COLUMN id SET DEFAULT nextval('public.layby_applications_id_seq'::regclass);


--
-- Name: layby_payments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.layby_payments ALTER COLUMN id SET DEFAULT nextval('public.layby_payments_id_seq'::regclass);


--
-- Name: layby_settings id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.layby_settings ALTER COLUMN id SET DEFAULT nextval('public.layby_settings_id_seq'::regclass);


--
-- Name: marketing_feedback id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.marketing_feedback ALTER COLUMN id SET DEFAULT nextval('public.marketing_feedback_id_seq'::regclass);


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
-- Name: order_reminder_emails id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_reminder_emails ALTER COLUMN id SET DEFAULT nextval('public.order_reminder_emails_id_seq'::regclass);


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
-- Name: product_brands id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_brands ALTER COLUMN id SET DEFAULT nextval('public.product_brands_id_seq'::regclass);


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
-- Name: promo_templates id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.promo_templates ALTER COLUMN id SET DEFAULT nextval('public.promo_templates_id_seq'::regclass);


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
-- Name: search_queries id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.search_queries ALTER COLUMN id SET DEFAULT nextval('public.search_queries_id_seq'::regclass);


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
-- Name: system_tickets id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.system_tickets ALTER COLUMN id SET DEFAULT nextval('public.system_tickets_id_seq'::regclass);


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
-- Name: ticket_activities id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_activities ALTER COLUMN id SET DEFAULT nextval('public.ticket_activities_id_seq'::regclass);


--
-- Name: ticket_messages id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_messages ALTER COLUMN id SET DEFAULT nextval('public.ticket_messages_id_seq'::regclass);


--
-- Name: tickets id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tickets ALTER COLUMN id SET DEFAULT nextval('public.tickets_id_seq'::regclass);


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
-- Name: vouchers id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vouchers ALTER COLUMN id SET DEFAULT nextval('public.vouchers_id_seq'::regclass);


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
-- Name: cash_book_categories cash_book_categories_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cash_book_categories
    ADD CONSTRAINT cash_book_categories_pkey PRIMARY KEY (id);


--
-- Name: cash_book_categories cash_book_categories_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cash_book_categories
    ADD CONSTRAINT cash_book_categories_slug_unique UNIQUE (slug);


--
-- Name: cash_book_entries cash_book_entries_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cash_book_entries
    ADD CONSTRAINT cash_book_entries_pkey PRIMARY KEY (id);


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
-- Name: commission_history_items commission_history_items_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.commission_history_items
    ADD CONSTRAINT commission_history_items_pkey PRIMARY KEY (id);


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
-- Name: import_jobs import_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.import_jobs
    ADD CONSTRAINT import_jobs_pkey PRIMARY KEY (id);


--
-- Name: inventory_receiving_temp inventory_receiving_temp_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory_receiving_temp
    ADD CONSTRAINT inventory_receiving_temp_pkey PRIMARY KEY (id);


--
-- Name: inventory_receiving_temp inventory_receiving_temp_user_id_shipment_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory_receiving_temp
    ADD CONSTRAINT inventory_receiving_temp_user_id_shipment_id_unique UNIQUE (user_id, shipment_id);


--
-- Name: inventory_shipment_history inventory_shipment_history_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory_shipment_history
    ADD CONSTRAINT inventory_shipment_history_pkey PRIMARY KEY (id);


--
-- Name: inventory_shipments inventory_shipments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory_shipments
    ADD CONSTRAINT inventory_shipments_pkey PRIMARY KEY (id);


--
-- Name: invoice_quotation_histories invoice_quotation_histories_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoice_quotation_histories
    ADD CONSTRAINT invoice_quotation_histories_pkey PRIMARY KEY (id);


--
-- Name: invoice_quotation_items invoice_quotation_items_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoice_quotation_items
    ADD CONSTRAINT invoice_quotation_items_pkey PRIMARY KEY (id);


--
-- Name: invoices_quotations invoices_quotations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoices_quotations
    ADD CONSTRAINT invoices_quotations_pkey PRIMARY KEY (id);


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
-- Name: layby_applications layby_applications_application_number_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.layby_applications
    ADD CONSTRAINT layby_applications_application_number_unique UNIQUE (application_number);


--
-- Name: layby_applications layby_applications_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.layby_applications
    ADD CONSTRAINT layby_applications_pkey PRIMARY KEY (id);


--
-- Name: layby_payments layby_payments_payment_number_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.layby_payments
    ADD CONSTRAINT layby_payments_payment_number_unique UNIQUE (payment_number);


--
-- Name: layby_payments layby_payments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.layby_payments
    ADD CONSTRAINT layby_payments_pkey PRIMARY KEY (id);


--
-- Name: layby_settings layby_settings_key_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.layby_settings
    ADD CONSTRAINT layby_settings_key_unique UNIQUE (key);


--
-- Name: layby_settings layby_settings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.layby_settings
    ADD CONSTRAINT layby_settings_pkey PRIMARY KEY (id);


--
-- Name: marketing_feedback marketing_feedback_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.marketing_feedback
    ADD CONSTRAINT marketing_feedback_pkey PRIMARY KEY (id);


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
-- Name: order_reminder_emails order_reminder_emails_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_reminder_emails
    ADD CONSTRAINT order_reminder_emails_pkey PRIMARY KEY (id);


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
-- Name: orders orders_feedback_token_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orders
    ADD CONSTRAINT orders_feedback_token_unique UNIQUE (feedback_token);


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
-- Name: product_brands product_brands_name_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_brands
    ADD CONSTRAINT product_brands_name_unique UNIQUE (name);


--
-- Name: product_brands product_brands_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_brands
    ADD CONSTRAINT product_brands_pkey PRIMARY KEY (id);


--
-- Name: product_brands product_brands_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_brands
    ADD CONSTRAINT product_brands_slug_unique UNIQUE (slug);


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
-- Name: promo_templates promo_templates_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.promo_templates
    ADD CONSTRAINT promo_templates_pkey PRIMARY KEY (id);


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
-- Name: search_queries search_queries_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.search_queries
    ADD CONSTRAINT search_queries_pkey PRIMARY KEY (id);


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
-- Name: system_tickets system_tickets_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.system_tickets
    ADD CONSTRAINT system_tickets_pkey PRIMARY KEY (id);


--
-- Name: system_tickets system_tickets_ticket_number_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.system_tickets
    ADD CONSTRAINT system_tickets_ticket_number_unique UNIQUE (ticket_number);


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
-- Name: ticket_activities ticket_activities_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_activities
    ADD CONSTRAINT ticket_activities_pkey PRIMARY KEY (id);


--
-- Name: ticket_messages ticket_messages_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_messages
    ADD CONSTRAINT ticket_messages_pkey PRIMARY KEY (id);


--
-- Name: tickets tickets_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT tickets_pkey PRIMARY KEY (id);


--
-- Name: tickets tickets_ticket_number_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT tickets_ticket_number_unique UNIQUE (ticket_number);


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
-- Name: users users_membership_card_number_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_membership_card_number_unique UNIQUE (membership_card_number);


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
-- Name: vouchers vouchers_code_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vouchers
    ADD CONSTRAINT vouchers_code_unique UNIQUE (code);


--
-- Name: vouchers vouchers_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vouchers
    ADD CONSTRAINT vouchers_pkey PRIMARY KEY (id);


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
-- Name: cart_abandonments_abandonment_stage_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cart_abandonments_abandonment_stage_index ON public.cart_abandonments USING btree (abandonment_stage);


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
-- Name: cash_book_entries_branch_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cash_book_entries_branch_index ON public.cash_book_entries USING btree (branch);


--
-- Name: cash_book_entries_category_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cash_book_entries_category_id_index ON public.cash_book_entries USING btree (category_id);


--
-- Name: cash_book_entries_entered_by_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cash_book_entries_entered_by_index ON public.cash_book_entries USING btree (entered_by);


--
-- Name: cash_book_entries_entry_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cash_book_entries_entry_date_index ON public.cash_book_entries USING btree (entry_date);


--
-- Name: cash_book_entries_reference_type_reference_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cash_book_entries_reference_type_reference_id_index ON public.cash_book_entries USING btree (reference_type, reference_id);


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
-- Name: categories_sort_order_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX categories_sort_order_index ON public.categories USING btree (sort_order);


--
-- Name: commission_history_items_commission_history_id_product_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX commission_history_items_commission_history_id_product_id_index ON public.commission_history_items USING btree (commission_history_id, product_id);


--
-- Name: commission_history_items_product_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX commission_history_items_product_id_index ON public.commission_history_items USING btree (product_id);


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
-- Name: idx_product_categories_category; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_product_categories_category ON public.product_categories USING btree (category_id);


--
-- Name: idx_product_categories_product; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_product_categories_product ON public.product_categories USING btree (product_id);


--
-- Name: idx_products_slug; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_products_slug ON public.products USING btree (slug);


--
-- Name: idx_products_status_updated; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_products_status_updated ON public.products USING btree (status, updated_at DESC) WHERE (deleted_at IS NULL);


--
-- Name: idx_products_trending_status; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_products_trending_status ON public.products USING btree (is_trending, status) WHERE (deleted_at IS NULL);


--
-- Name: import_jobs_batch_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX import_jobs_batch_id_index ON public.import_jobs USING btree (batch_id);


--
-- Name: import_jobs_batch_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX import_jobs_batch_id_status_index ON public.import_jobs USING btree (batch_id, status);


--
-- Name: import_jobs_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX import_jobs_created_at_index ON public.import_jobs USING btree (created_at);


--
-- Name: import_jobs_import_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX import_jobs_import_type_index ON public.import_jobs USING btree (import_type);


--
-- Name: inventory_receiving_temp_shipment_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX inventory_receiving_temp_shipment_id_index ON public.inventory_receiving_temp USING btree (shipment_id);


--
-- Name: inventory_receiving_temp_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX inventory_receiving_temp_user_id_index ON public.inventory_receiving_temp USING btree (user_id);


--
-- Name: inventory_shipment_history_shipment_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX inventory_shipment_history_shipment_id_created_at_index ON public.inventory_shipment_history USING btree (shipment_id, created_at);


--
-- Name: inventory_shipment_history_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX inventory_shipment_history_user_id_index ON public.inventory_shipment_history USING btree (user_id);


--
-- Name: inventory_shipments_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX inventory_shipments_created_at_index ON public.inventory_shipments USING btree (created_at);


--
-- Name: inventory_shipments_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX inventory_shipments_date_index ON public.inventory_shipments USING btree (date);


--
-- Name: inventory_shipments_destination_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX inventory_shipments_destination_index ON public.inventory_shipments USING btree (destination);


--
-- Name: inventory_shipments_eta_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX inventory_shipments_eta_index ON public.inventory_shipments USING btree (eta);


--
-- Name: inventory_shipments_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX inventory_shipments_status_index ON public.inventory_shipments USING btree (status);


--
-- Name: invoice_quotation_histories_action_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX invoice_quotation_histories_action_index ON public.invoice_quotation_histories USING btree (action);


--
-- Name: invoice_quotation_histories_invoice_quotation_id_created_at_ind; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX invoice_quotation_histories_invoice_quotation_id_created_at_ind ON public.invoice_quotation_histories USING btree (invoice_quotation_id, created_at);


--
-- Name: invoice_quotation_histories_user_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX invoice_quotation_histories_user_id_created_at_index ON public.invoice_quotation_histories USING btree (user_id, created_at);


--
-- Name: invoices_quotations_currency_code_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX invoices_quotations_currency_code_index ON public.invoices_quotations USING btree (currency_code);


--
-- Name: invoices_quotations_document_number_unique; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX invoices_quotations_document_number_unique ON public.invoices_quotations USING btree (document_number) WHERE ((status)::text <> 'autosave'::text);


--
-- Name: invoices_quotations_document_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX invoices_quotations_document_type_index ON public.invoices_quotations USING btree (document_type);


--
-- Name: invoices_quotations_issue_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX invoices_quotations_issue_date_index ON public.invoices_quotations USING btree (issue_date);


--
-- Name: invoices_quotations_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX invoices_quotations_status_index ON public.invoices_quotations USING btree (status);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: layby_applications_application_number_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX layby_applications_application_number_index ON public.layby_applications USING btree (application_number);


--
-- Name: layby_applications_order_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX layby_applications_order_id_index ON public.layby_applications USING btree (order_id);


--
-- Name: layby_applications_user_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX layby_applications_user_id_status_index ON public.layby_applications USING btree (user_id, status);


--
-- Name: layby_payments_layby_application_id_payment_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX layby_payments_layby_application_id_payment_status_index ON public.layby_payments USING btree (layby_application_id, payment_status);


--
-- Name: marketing_feedback_country_code_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX marketing_feedback_country_code_index ON public.marketing_feedback USING btree (country_code);


--
-- Name: marketing_feedback_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX marketing_feedback_created_at_index ON public.marketing_feedback USING btree (created_at);


--
-- Name: marketing_feedback_heard_about_source_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX marketing_feedback_heard_about_source_index ON public.marketing_feedback USING btree (heard_about_source);


--
-- Name: marketing_feedback_order_number_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX marketing_feedback_order_number_index ON public.marketing_feedback USING btree (order_number);


--
-- Name: marketing_feedback_ordering_process_rating_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX marketing_feedback_ordering_process_rating_index ON public.marketing_feedback USING btree (ordering_process_rating);


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
-- Name: order_products_order_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX order_products_order_idx ON public.order_products USING btree (order_id, product_id);


--
-- Name: order_reminder_emails_order_id_reminder_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX order_reminder_emails_order_id_reminder_type_index ON public.order_reminder_emails USING btree (order_id, reminder_type);


--
-- Name: order_reminder_emails_sent_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX order_reminder_emails_sent_at_index ON public.order_reminder_emails USING btree (sent_at);


--
-- Name: order_status_histories_order_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX order_status_histories_order_id_index ON public.order_status_histories USING btree (order_id);


--
-- Name: orders_filter_sort_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX orders_filter_sort_idx ON public.orders USING btree (parent_id, order_status_id, created_at);


--
-- Name: orders_number_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX orders_number_idx ON public.orders USING btree (order_number);


--
-- Name: page_views_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX page_views_created_at_index ON public.page_views USING btree (created_at);


--
-- Name: page_views_path_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX page_views_path_index ON public.page_views USING btree (path);


--
-- Name: page_views_session_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX page_views_session_id_index ON public.page_views USING btree (session_id);


--
-- Name: page_views_user_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX page_views_user_id_created_at_index ON public.page_views USING btree (user_id, created_at);


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
-- Name: product_categories_lookup_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX product_categories_lookup_idx ON public.product_categories USING btree (category_id, product_id);


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
-- Name: products_filter_sort_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX products_filter_sort_idx ON public.products USING btree (status, stock_status, created_at);


--
-- Name: products_name_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX products_name_index ON public.products USING btree (name);


--
-- Name: products_price_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX products_price_index ON public.products USING btree (price);


--
-- Name: products_search_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX products_search_idx ON public.products USING btree (sku, name);


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
-- Name: products_store_approval_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX products_store_approval_idx ON public.products USING btree (store_id, is_approved, status, deleted_at);


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
-- Name: search_queries_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX search_queries_created_at_index ON public.search_queries USING btree (created_at);


--
-- Name: search_queries_normalized_query_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX search_queries_normalized_query_created_at_index ON public.search_queries USING btree (normalized_query, created_at);


--
-- Name: search_queries_normalized_query_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX search_queries_normalized_query_index ON public.search_queries USING btree (normalized_query);


--
-- Name: search_queries_query_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX search_queries_query_created_at_index ON public.search_queries USING btree (query, created_at);


--
-- Name: search_queries_query_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX search_queries_query_index ON public.search_queries USING btree (query);


--
-- Name: search_queries_session_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX search_queries_session_id_index ON public.search_queries USING btree (session_id);


--
-- Name: search_queries_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX search_queries_user_id_index ON public.search_queries USING btree (user_id);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: stores_filter_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX stores_filter_idx ON public.stores USING btree (is_approved, status, is_banned, created_at);


--
-- Name: system_tickets_assigned_to_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX system_tickets_assigned_to_index ON public.system_tickets USING btree (assigned_to);


--
-- Name: system_tickets_created_by_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX system_tickets_created_by_index ON public.system_tickets USING btree (created_by);


--
-- Name: system_tickets_priority_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX system_tickets_priority_index ON public.system_tickets USING btree (priority);


--
-- Name: system_tickets_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX system_tickets_status_index ON public.system_tickets USING btree (status);


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
-- Name: ticket_activities_action_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_activities_action_index ON public.ticket_activities USING btree (action);


--
-- Name: ticket_activities_ticket_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_activities_ticket_id_index ON public.ticket_activities USING btree (ticket_id);


--
-- Name: ticket_activities_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_activities_user_id_index ON public.ticket_activities USING btree (user_id);


--
-- Name: ticket_messages_ticket_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_messages_ticket_id_index ON public.ticket_messages USING btree (ticket_id);


--
-- Name: tickets_assigned_to_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tickets_assigned_to_status_index ON public.tickets USING btree (assigned_to, status);


--
-- Name: tickets_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tickets_status_index ON public.tickets USING btree (status);


--
-- Name: tickets_user_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tickets_user_id_status_index ON public.tickets USING btree (user_id, status);


--
-- Name: user_events_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_events_created_at_index ON public.user_events USING btree (created_at);


--
-- Name: user_events_event_name_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_events_event_name_index ON public.user_events USING btree (event_name);


--
-- Name: user_events_event_type_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_events_event_type_created_at_index ON public.user_events USING btree (event_type, created_at);


--
-- Name: user_events_event_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_events_event_type_index ON public.user_events USING btree (event_type);


--
-- Name: user_events_session_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_events_session_id_index ON public.user_events USING btree (session_id);


--
-- Name: user_events_user_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_events_user_id_created_at_index ON public.user_events USING btree (user_id, created_at);


--
-- Name: user_sessions_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_sessions_created_at_index ON public.user_sessions USING btree (created_at);


--
-- Name: user_sessions_last_activity_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_sessions_last_activity_at_index ON public.user_sessions USING btree (last_activity_at);


--
-- Name: user_sessions_session_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_sessions_session_id_index ON public.user_sessions USING btree (session_id);


--
-- Name: user_sessions_user_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_sessions_user_id_created_at_index ON public.user_sessions USING btree (user_id, created_at);


--
-- Name: user_sessions_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_sessions_user_id_index ON public.user_sessions USING btree (user_id);


--
-- Name: users_branch_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX users_branch_index ON public.users USING btree (branch);


--
-- Name: users_search_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX users_search_idx ON public.users USING btree (name, email, deleted_at);


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
-- Name: vouchers_code_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX vouchers_code_index ON public.vouchers USING btree (code);


--
-- Name: vouchers_purchased_by_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX vouchers_purchased_by_created_at_index ON public.vouchers USING btree (purchased_by, created_at);


--
-- Name: vouchers_redeemed_by_redeemed_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX vouchers_redeemed_by_redeemed_at_index ON public.vouchers USING btree (redeemed_by, redeemed_at);


--
-- Name: vouchers_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX vouchers_status_index ON public.vouchers USING btree (status);


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
-- Name: cart_abandonments cart_abandonments_order_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cart_abandonments
    ADD CONSTRAINT cart_abandonments_order_id_foreign FOREIGN KEY (order_id) REFERENCES public.orders(id) ON DELETE SET NULL;


--
-- Name: cart_abandonments cart_abandonments_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cart_abandonments
    ADD CONSTRAINT cart_abandonments_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


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
-- Name: cash_book_entries cash_book_entries_entered_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cash_book_entries
    ADD CONSTRAINT cash_book_entries_entered_by_foreign FOREIGN KEY (entered_by) REFERENCES public.users(id) ON DELETE SET NULL;


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
-- Name: commission_history_items commission_history_items_commission_history_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.commission_history_items
    ADD CONSTRAINT commission_history_items_commission_history_id_foreign FOREIGN KEY (commission_history_id) REFERENCES public.commission_histories(id) ON DELETE CASCADE;


--
-- Name: commission_history_items commission_history_items_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.commission_history_items
    ADD CONSTRAINT commission_history_items_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id);


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
-- Name: inventory_receiving_temp inventory_receiving_temp_shipment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory_receiving_temp
    ADD CONSTRAINT inventory_receiving_temp_shipment_id_foreign FOREIGN KEY (shipment_id) REFERENCES public.inventory_shipments(id) ON DELETE CASCADE;


--
-- Name: inventory_receiving_temp inventory_receiving_temp_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory_receiving_temp
    ADD CONSTRAINT inventory_receiving_temp_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: inventory_shipment_history inventory_shipment_history_shipment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory_shipment_history
    ADD CONSTRAINT inventory_shipment_history_shipment_id_foreign FOREIGN KEY (shipment_id) REFERENCES public.inventory_shipments(id) ON DELETE CASCADE;


--
-- Name: inventory_shipment_history inventory_shipment_history_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory_shipment_history
    ADD CONSTRAINT inventory_shipment_history_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: inventory_shipments inventory_shipments_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory_shipments
    ADD CONSTRAINT inventory_shipments_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: inventory_shipments inventory_shipments_received_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory_shipments
    ADD CONSTRAINT inventory_shipments_received_by_foreign FOREIGN KEY (received_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: inventory_shipments inventory_shipments_signed_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory_shipments
    ADD CONSTRAINT inventory_shipments_signed_by_foreign FOREIGN KEY (signed_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: inventory_shipments inventory_shipments_updated_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory_shipments
    ADD CONSTRAINT inventory_shipments_updated_by_foreign FOREIGN KEY (updated_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: invoice_quotation_histories invoice_quotation_histories_invoice_quotation_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoice_quotation_histories
    ADD CONSTRAINT invoice_quotation_histories_invoice_quotation_id_foreign FOREIGN KEY (invoice_quotation_id) REFERENCES public.invoices_quotations(id) ON DELETE CASCADE;


--
-- Name: invoice_quotation_histories invoice_quotation_histories_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoice_quotation_histories
    ADD CONSTRAINT invoice_quotation_histories_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: invoice_quotation_items invoice_quotation_items_invoice_quotation_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoice_quotation_items
    ADD CONSTRAINT invoice_quotation_items_invoice_quotation_id_foreign FOREIGN KEY (invoice_quotation_id) REFERENCES public.invoices_quotations(id) ON DELETE CASCADE;


--
-- Name: invoice_quotation_items invoice_quotation_items_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoice_quotation_items
    ADD CONSTRAINT invoice_quotation_items_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE SET NULL;


--
-- Name: invoice_quotation_items invoice_quotation_items_variation_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoice_quotation_items
    ADD CONSTRAINT invoice_quotation_items_variation_id_foreign FOREIGN KEY (variation_id) REFERENCES public.variations(id) ON DELETE SET NULL;


--
-- Name: invoices_quotations invoices_quotations_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoices_quotations
    ADD CONSTRAINT invoices_quotations_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: invoices_quotations invoices_quotations_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoices_quotations
    ADD CONSTRAINT invoices_quotations_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: layby_applications layby_applications_approved_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.layby_applications
    ADD CONSTRAINT layby_applications_approved_by_foreign FOREIGN KEY (approved_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: layby_applications layby_applications_cancelled_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.layby_applications
    ADD CONSTRAINT layby_applications_cancelled_by_foreign FOREIGN KEY (cancelled_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: layby_applications layby_applications_order_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.layby_applications
    ADD CONSTRAINT layby_applications_order_id_foreign FOREIGN KEY (order_id) REFERENCES public.orders(id) ON DELETE SET NULL;


--
-- Name: layby_applications layby_applications_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.layby_applications
    ADD CONSTRAINT layby_applications_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: layby_applications layby_applications_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.layby_applications
    ADD CONSTRAINT layby_applications_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: layby_applications layby_applications_variation_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.layby_applications
    ADD CONSTRAINT layby_applications_variation_id_foreign FOREIGN KEY (variation_id) REFERENCES public.variations(id) ON DELETE SET NULL;


--
-- Name: layby_payments layby_payments_captured_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.layby_payments
    ADD CONSTRAINT layby_payments_captured_by_foreign FOREIGN KEY (captured_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: layby_payments layby_payments_layby_application_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.layby_payments
    ADD CONSTRAINT layby_payments_layby_application_id_foreign FOREIGN KEY (layby_application_id) REFERENCES public.layby_applications(id) ON DELETE CASCADE;


--
-- Name: marketing_feedback marketing_feedback_order_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.marketing_feedback
    ADD CONSTRAINT marketing_feedback_order_id_foreign FOREIGN KEY (order_id) REFERENCES public.orders(id) ON DELETE SET NULL;


--
-- Name: marketing_feedback marketing_feedback_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.marketing_feedback
    ADD CONSTRAINT marketing_feedback_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


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
-- Name: order_products order_products_inventory_shipment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_products
    ADD CONSTRAINT order_products_inventory_shipment_id_foreign FOREIGN KEY (inventory_shipment_id) REFERENCES public.inventory_shipments(id) ON DELETE SET NULL;


--
-- Name: order_products order_products_order_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_products
    ADD CONSTRAINT order_products_order_id_foreign FOREIGN KEY (order_id) REFERENCES public.orders(id) ON DELETE CASCADE;


--
-- Name: order_products order_products_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_products
    ADD CONSTRAINT order_products_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE SET NULL;


--
-- Name: order_products order_products_variation_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_products
    ADD CONSTRAINT order_products_variation_id_foreign FOREIGN KEY (variation_id) REFERENCES public.variations(id) ON DELETE SET NULL;


--
-- Name: order_reminder_emails order_reminder_emails_order_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_reminder_emails
    ADD CONSTRAINT order_reminder_emails_order_id_foreign FOREIGN KEY (order_id) REFERENCES public.orders(id) ON DELETE CASCADE;


--
-- Name: order_reminder_emails order_reminder_emails_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_reminder_emails
    ADD CONSTRAINT order_reminder_emails_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


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
-- Name: page_views page_views_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.page_views
    ADD CONSTRAINT page_views_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


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
-- Name: product_brands product_brands_brand_image_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_brands
    ADD CONSTRAINT product_brands_brand_image_id_foreign FOREIGN KEY (brand_image_id) REFERENCES public.attachments(id) ON DELETE SET NULL;


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
-- Name: products products_brand_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_brand_id_foreign FOREIGN KEY (brand_id) REFERENCES public.product_brands(id) ON DELETE SET NULL;


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
-- Name: search_queries search_queries_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.search_queries
    ADD CONSTRAINT search_queries_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


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
-- Name: stores stores_banned_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stores
    ADD CONSTRAINT stores_banned_by_foreign FOREIGN KEY (banned_by) REFERENCES public.users(id) ON DELETE SET NULL;


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
-- Name: stores stores_product_catalog_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stores
    ADD CONSTRAINT stores_product_catalog_id_foreign FOREIGN KEY (product_catalog_id) REFERENCES public.attachments(id) ON DELETE SET NULL;


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
-- Name: system_tickets system_tickets_assigned_to_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.system_tickets
    ADD CONSTRAINT system_tickets_assigned_to_foreign FOREIGN KEY (assigned_to) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: system_tickets system_tickets_closed_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.system_tickets
    ADD CONSTRAINT system_tickets_closed_by_foreign FOREIGN KEY (closed_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: system_tickets system_tickets_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.system_tickets
    ADD CONSTRAINT system_tickets_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE CASCADE;


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
-- Name: ticket_activities ticket_activities_ticket_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_activities
    ADD CONSTRAINT ticket_activities_ticket_id_foreign FOREIGN KEY (ticket_id) REFERENCES public.system_tickets(id) ON DELETE CASCADE;


--
-- Name: ticket_activities ticket_activities_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_activities
    ADD CONSTRAINT ticket_activities_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: ticket_messages ticket_messages_ticket_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_messages
    ADD CONSTRAINT ticket_messages_ticket_id_foreign FOREIGN KEY (ticket_id) REFERENCES public.tickets(id) ON DELETE CASCADE;


--
-- Name: ticket_messages ticket_messages_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_messages
    ADD CONSTRAINT ticket_messages_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: tickets tickets_assigned_to_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT tickets_assigned_to_foreign FOREIGN KEY (assigned_to) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: tickets tickets_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT tickets_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


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
-- Name: user_events user_events_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_events
    ADD CONSTRAINT user_events_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: user_sessions user_sessions_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_sessions
    ADD CONSTRAINT user_sessions_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


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
-- Name: vouchers vouchers_order_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vouchers
    ADD CONSTRAINT vouchers_order_id_foreign FOREIGN KEY (order_id) REFERENCES public.orders(id) ON DELETE SET NULL;


--
-- Name: vouchers vouchers_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vouchers
    ADD CONSTRAINT vouchers_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE SET NULL;


--
-- Name: vouchers vouchers_purchased_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vouchers
    ADD CONSTRAINT vouchers_purchased_by_foreign FOREIGN KEY (purchased_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: vouchers vouchers_redeemed_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vouchers
    ADD CONSTRAINT vouchers_redeemed_by_foreign FOREIGN KEY (redeemed_by) REFERENCES public.users(id) ON DELETE SET NULL;


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
-- Name: withdraw_requests withdraw_requests_approved_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.withdraw_requests
    ADD CONSTRAINT withdraw_requests_approved_by_foreign FOREIGN KEY (approved_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: withdraw_requests withdraw_requests_rejected_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.withdraw_requests
    ADD CONSTRAINT withdraw_requests_rejected_by_foreign FOREIGN KEY (rejected_by) REFERENCES public.users(id) ON DELETE SET NULL;


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

\unrestrict v5WFgBaAHsI7gvrob6xF7MHugWOjgKwGpWgO9D0HYW0fvRbJ2ISgSRsirnRgIXo

--
-- PostgreSQL database dump
--

\restrict DRNmr3zrSIrAhetLmmK61n9o5Eo5d8cwb32t5hD8KkuMJiwW2UanFhuTETBlXzA

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
1	0001_01_01_000000_create_users_table	1
2	0001_01_01_000001_create_cache_table	1
3	0001_01_01_000002_create_jobs_table	1
4	2014_11_29_1000004_create_attachment_table	1
5	2018_08_08_100000_create_telescope_entries_table	1
6	2021_11_25_094447_create_countries_table	1
7	2021_11_25_120136_create_taxes_table	1
8	2022_09_23_090305_create_attributes_table	1
9	2022_09_28_105314_create_categories_table	1
10	2022_10_01_135505_create_tags_table	1
11	2022_10_17_035823_create_themes_table	1
12	2022_11_09_072500_create_stores_table	1
13	2022_11_12_053826_create_products_table	1
14	2022_11_17_111446_create_blogs_table	1
15	2022_11_30_040956_create_shippings_table	1
16	2022_12_03_041404_create_coupons_table	1
17	2022_12_08_092552_create_settings_table	1
18	2023_01_13_060558_create_addresses_table	1
19	2023_01_24_084530_create_orders_table	1
20	2023_02_01_035655_create_wallets_table	1
21	2023_02_10_053509_create_theme_options_table	1
22	2023_02_16_132426_create_currencies_table	1
23	2023_02_17_043333_create_pages_table	1
24	2023_02_24_054056_create_home_pages_table	1
25	2023_03_01_050232_create_wishlists_table	1
26	2023_03_01_100808_create_carts_table	1
27	2023_03_02_033848_create_compares_table	1
28	2023_04_05_042121_create_vendor_wallets_table	1
29	2023_04_05_062421_create_commission_histories_table	1
30	2023_04_05_062439_create_withdraw_requests_table	1
31	2023_04_06_051415_create_payment_accounts_table	1
32	2023_04_06_133831_create_vendor_transactions_table	1
33	2023_04_11_120059_create_faqs_table	1
34	2023_04_14_110653_create_reviews_table	1
35	2023_04_20_044705_create_notifications_table	1
36	2023_04_24_050852_create_refunds_table	1
37	2023_04_24_050852_create_seeders_table	1
38	2023_09_12_045133_create_question_and_answers_table	1
39	2025_01_07_090631_create_personal_access_tokens_table	1
40	2025_01_07_093454_create_permission_tables	1
41	2025_01_07_101611_create_media_table	1
42	2025_01_26_102503_add_indexes_to_tables	1
43	2025_02_13_112911_add_created_at_index_to_reviews_table	1
44	2025_02_15_153807_add_takealot_url_column_to_attachements_table	1
45	2025_02_19_141506_add_media_id_to_attachments_table	1
46	2025_03_19_104113_add_column_search_keywords_on_products_table	1
47	2025_03_19_113052_add_search_tsv_to_products	1
48	2025_03_19_121439_add_pg_trgm_extension_and_trigram_index_to_products	1
49	2025_03_23_175211_add_category_icon_and_image_uuid_column_to_categories_table	1
50	2025_04_26_161805_add_column_delivery_price_to_orders_table	1
51	2025_10_18_055234_create_modules_table	1
52	2025_07_16_000000_create_pesepay_transactions_table	2
53	2025_07_18_000000_create_payfast_transactions_table	2
54	2025_09_05_000001_add_note_to_orders_table	3
55	2025_09_07_000001_create_order_status_histories_table	4
56	2025_09_14_000001_add_currency_fields_to_orders_table	5
57	2025_09_14_000002_add_exchange_rate_to_orders_table	5
58	2025_09_22_000000_create_dpo_zambia_transactions_table	6
59	2025_09_22_000001_create_payfast_transactions_table	7
60	2025_09_22_000002_create_pesepay_transactions_table	7
61	2025_09_24_000001_create_order_notes_table	7
62	2025_10_06_000000_create_yoco_transactions_table	8
63	2025_10_06_000001_add_order_number_to_yoco_transactions_table	8
64	2025_10_07_090508_create_returns_table	9
65	2025_10_07_133651_add_preferred_outcome_to_returns_table	9
66	2025_10_07_141209_add_unique_index_on_returns_user_order_product	9
67	2025_10_11_120000_add_expedited_shipping_to_products_and_carts	9
68	2025_10_11_140000_add_fast_shipping_total_to_orders	9
69	2025_10_12_000001_add_rejection_reason_to_returns_table	10
70	2025_10_15_000001_add_fast_shipping_to_order_products	11
71	2025_10_18_000001_change_shipping_days_to_string	11
72	2025_10_18_120000_add_item_status_to_order_products	11
73	2025_10_20_000001_add_eta_to_order_products	11
74	2025_11_09_000001_add_selected_attributes_to_order_products	12
75	2025_11_12_000001_add_currency_preference_to_users_table	13
76	2025_11_19_092904_create_page_views_table	14
77	2025_11_19_092956_create_user_sessions_table	14
78	2025_11_19_092957_create_cart_abandonments_table	14
79	2025_11_19_092958_create_user_events_table	14
80	2025_12_06_000001_create_layby_applications_table	15
81	2025_12_06_000002_create_layby_payments_table	15
82	2025_12_06_092221_add_id_upload_to_layby_applications_table	15
83	2025_12_06_092639_create_layby_settings_table	15
84	2025_12_06_100000_add_gateway_fields_to_layby_payments	15
85	2025_12_06_120000_add_order_id_to_layby_applications	15
86	2025_12_06_184210_add_id_document_attachment_id_to_layby_applications_table	16
87	2025_12_07_000001_create_marketing_feedback_table	17
88	2025_12_07_000002_add_country_to_marketing_feedback	17
89	2025_12_09_102014_create_order_reminder_emails_table	18
90	2025_12_09_185007_add_feedback_token_to_orders_table	18
91	2025_12_16_183250_add_cancellation_fields_to_layby_applications_table	19
92	2025_12_17_000000_add_vendor_registration_fields_to_stores_table	20
93	2025_12_18_000000_create_inventory_shipments_table	21
94	2025_12_18_154822_update_inventory_shipments_signed_by_to_foreign_key	21
95	2025_12_18_161130_add_is_banned_to_stores_table	21
96	2025_12_19_164343_add_approval_fields_to_withdraw_requests_table	22
97	2025_12_21_152416_add_original_currency_fields_to_products_table	22
98	2025_12_22_000001_add_vendor_applications_performance_indexes	22
99	2025_12_22_000002_add_orders_performance_indexes	22
100	2025_12_22_000003_add_products_performance_indexes	22
101	2025_12_22_180000_create_commission_history_items_table	22
102	2025_12_23_180100_add_payment_details_to_withdraw_requests	22
103	2025_12_24_000001_add_assigned_to_to_inventory_shipments_table	22
104	2025_12_28_000000_create_import_jobs_table	23
105	2025_12_29_000001_create_product_brands_table	24
106	2025_12_29_000002_add_brand_id_to_products_table	24
107	2025_12_29_131917_create_promo_templates_table	24
108	2026_01_02_163305_add_import_type_to_import_jobs_table	25
109	2026_01_05_000001_create_tickets_table	26
110	2026_01_07_200000_add_order_id_to_pesepay_transactions_if_missing	27
111	2026_01_12_150000_create_invoices_quotations_table	28
112	2026_01_13_150257_create_invoice_quotation_histories_table	29
113	2026_01_13_173129_create_vouchers_table	30
114	2026_01_13_173703_add_gift_card_fields_to_products_table	30
115	2026_01_13_174156_add_non_cashable_balance_to_wallets_table	30
116	2026_01_13_195647_add_shipping_fields_to_invoices_quotations_table	30
117	2026_01_14_123313_add_is_gift_order_to_orders_table	30
118	2026_01_14_150625_add_indexes_to_analytics_tables	30
119	2026_01_14_201250_change_value_column_to_decimal_in_user_events_table	31
120	2026_01_14_220000_change_value_column_to_decimal_in_user_events_table	31
121	2026_01_14_220100_add_vendor_required_columns_to_pesepay_transactions	31
122	2026_01_15_000000_add_amount_and_currency_to_pesepay_transactions	32
123	2026_01_16_201332_add_specifications_column_to_products_table	33
124	2026_01_18_103901_create_search_queries_table	34
125	2026_01_19_120000_add_refunded_and_arrived_at_local_branch_statuses	35
126	2026_01_20_095900_add_inventory_shipment_tracking_to_order_products_table	36
127	2026_01_21_150339_create_cash_book_entries_table	37
128	2026_01_21_150356_create_cash_book_categories_table	37
129	2026_01_21_173101_add_branch_to_cash_book_entries	37
130	2026_01_21_192302_add_qr_code_to_order_products_table	38
131	2026_01_22_111451_create_inventory_receiving_temp_table	39
132	2026_01_22_200000_add_branch_to_users_table	39
133	2026_01_23_222802_add_membership_card_number_to_users_table	40
134	2026_01_27_122829_create_system_tickets_and_ticket_activities_tables	41
135	2026_01_29_000001_add_warranty_to_products_table	42
136	2026_02_05_120000_fix_order_products_cascade_delete_on_product	43
137	2026_02_05_000001_add_estimated_delivery_text_to_order_products	44
139	2026_02_13_081517_add_autosave_status_to_invoices_quotations_table	45
140	2026_02_08_000001_create_analytics_page_views_table	46
141	2026_02_08_000002_create_analytics_user_sessions_table	46
142	2026_02_08_000003_create_analytics_user_events_table	46
143	2026_02_08_000004_create_analytics_cart_abandonments_table	46
144	2026_02_14_000000_modify_document_number_unique_constraint	47
145	2026_02_26_092150_add_qr_code_url_to_orders_table	48
146	2026_02_27_000001_add_sort_order_to_categories_table	49
147	2026_03_02_102019_cleanup_temp_layby_orders	50
\.


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.migrations_id_seq', 147, true);


--
-- PostgreSQL database dump complete
--

\unrestrict DRNmr3zrSIrAhetLmmK61n9o5Eo5d8cwb32t5hD8KkuMJiwW2UanFhuTETBlXzA

