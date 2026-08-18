-- =====================================================================
-- DATABASE: SHOP BÁN QUẦN ÁO (MySQL 8.0+)
-- Bao gồm: Sản phẩm & biến thể, Kho hàng chi tiết, Nhập hàng,
-- Khách hàng, Nhân viên, Đơn hàng, Thanh toán, Giao hàng chi tiết,
-- Đổi trả, Khuyến mãi, Đánh giá
-- =====================================================================

CREATE DATABASE IF NOT EXISTS shop_quanao
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE shop_quanao;

SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================================
-- 1. DANH MỤC & THƯƠNG HIỆU
-- =====================================================================

CREATE TABLE categories (
    category_id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id       INT UNSIGNED NULL COMMENT 'Danh mục cha (NULL = danh mục gốc)',
    name            VARCHAR(150) NOT NULL,
    slug            VARCHAR(160) NOT NULL UNIQUE,
    description     TEXT NULL,
    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_category_parent FOREIGN KEY (parent_id) REFERENCES categories(category_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE brands (
    brand_id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(150) NOT NULL UNIQUE,
    logo_url        VARCHAR(500) NULL,
    description     TEXT NULL,
    is_active       TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- =====================================================================
-- 2. SẢN PHẨM & BIẾN THỂ (Size / Màu / Chất liệu)
-- =====================================================================

CREATE TABLE products (
    product_id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id     INT UNSIGNED NOT NULL,
    brand_id        INT UNSIGNED NULL,
    name            VARCHAR(255) NOT NULL,
    slug            VARCHAR(270) NOT NULL UNIQUE,
    description     TEXT NULL,
    gender          ENUM('nam','nu','unisex','tre_em') NOT NULL DEFAULT 'unisex',
    material        VARCHAR(150) NULL COMMENT 'Chất liệu chung (cotton, kaki, jean...)',
    season          ENUM('xuan_he','thu_dong','quanh_nam') NOT NULL DEFAULT 'quanh_nam',
    base_price      DECIMAL(12,0) NOT NULL COMMENT 'Giá niêm yết mặc định (VNĐ)',
    status          ENUM('draft','active','discontinued') NOT NULL DEFAULT 'active',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_product_category FOREIGN KEY (category_id) REFERENCES categories(category_id),
    CONSTRAINT fk_product_brand FOREIGN KEY (brand_id) REFERENCES brands(brand_id),
    INDEX idx_product_status (status)
) ENGINE=InnoDB;

-- Thuộc tính dùng chung (Size, Màu sắc, Chất liệu...) để mở rộng linh hoạt
CREATE TABLE attributes (
    attribute_id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100) NOT NULL UNIQUE COMMENT 'VD: Size, Màu sắc'
) ENGINE=InnoDB;

CREATE TABLE attribute_values (
    attribute_value_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    attribute_id        INT UNSIGNED NOT NULL,
    value                VARCHAR(100) NOT NULL COMMENT 'VD: S, M, L / Đỏ, Xanh',
    CONSTRAINT fk_attrval_attr FOREIGN KEY (attribute_id) REFERENCES attributes(attribute_id) ON DELETE CASCADE,
    UNIQUE KEY uq_attr_value (attribute_id, value)
) ENGINE=InnoDB;

-- Biến thể sản phẩm = SKU thực tế được bán/lưu kho
CREATE TABLE product_variants (
    variant_id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id      INT UNSIGNED NOT NULL,
    sku             VARCHAR(64) NOT NULL UNIQUE,
    barcode         VARCHAR(64) NULL UNIQUE,
    price           DECIMAL(12,0) NOT NULL COMMENT 'Giá bán biến thể (có thể khác base_price)',
    cost_price      DECIMAL(12,0) NOT NULL COMMENT 'Giá vốn nhập hàng',
    weight_gram     INT UNSIGNED NOT NULL DEFAULT 300 COMMENT 'Trọng lượng để tính phí ship',
    image_url       VARCHAR(500) NULL,
    status          ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_variant_product FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Gán thuộc tính (size/màu...) cho từng biến thể
CREATE TABLE product_variant_attributes (
    variant_id          INT UNSIGNED NOT NULL,
    attribute_value_id  INT UNSIGNED NOT NULL,
    PRIMARY KEY (variant_id, attribute_value_id),
    CONSTRAINT fk_pva_variant FOREIGN KEY (variant_id) REFERENCES product_variants(variant_id) ON DELETE CASCADE,
    CONSTRAINT fk_pva_attrval FOREIGN KEY (attribute_value_id) REFERENCES attribute_values(attribute_value_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE product_images (
    image_id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id      INT UNSIGNED NOT NULL,
    variant_id      INT UNSIGNED NULL COMMENT 'NULL = ảnh chung sản phẩm',
    url             VARCHAR(500) NOT NULL,
    is_primary      TINYINT(1) NOT NULL DEFAULT 0,
    sort_order      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT fk_img_product FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    CONSTRAINT fk_img_variant FOREIGN KEY (variant_id) REFERENCES product_variants(variant_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- 3. KHO HÀNG (chi tiết: vị trí kệ, tồn kho, nhập/xuất, kiểm kê)
-- =====================================================================

CREATE TABLE warehouses (
    warehouse_id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(150) NOT NULL,
    address         VARCHAR(255) NOT NULL,
    phone           VARCHAR(20) NULL,
    manager_name    VARCHAR(150) NULL,
    is_active       TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- Vị trí cụ thể trong kho (khu vực - dãy - kệ - tầng) để định vị hàng hoá
CREATE TABLE warehouse_locations (
    location_id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    warehouse_id    INT UNSIGNED NOT NULL,
    zone            VARCHAR(20) NOT NULL COMMENT 'Khu vực, VD: A, B, C',
    aisle           VARCHAR(20) NOT NULL COMMENT 'Dãy, VD: 01, 02',
    shelf           VARCHAR(20) NOT NULL COMMENT 'Kệ, VD: 03',
    bin             VARCHAR(20) NOT NULL COMMENT 'Ô/tầng, VD: 02',
    code            VARCHAR(60) GENERATED ALWAYS AS (CONCAT(zone,'-',aisle,'-',shelf,'-',bin)) STORED,
    CONSTRAINT fk_loc_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouses(warehouse_id) ON DELETE CASCADE,
    UNIQUE KEY uq_location (warehouse_id, zone, aisle, shelf, bin)
) ENGINE=InnoDB;

-- Tồn kho theo từng biến thể + kho + vị trí cụ thể
CREATE TABLE inventory (
    inventory_id        BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    variant_id           INT UNSIGNED NOT NULL,
    warehouse_id         INT UNSIGNED NOT NULL,
    location_id           INT UNSIGNED NULL,
    quantity_on_hand     INT NOT NULL DEFAULT 0 COMMENT 'Tồn kho thực tế',
    quantity_reserved    INT NOT NULL DEFAULT 0 COMMENT 'Đã giữ cho đơn hàng chưa xuất kho',
    quantity_available   INT GENERATED ALWAYS AS (quantity_on_hand - quantity_reserved) STORED,
    reorder_level        INT NOT NULL DEFAULT 10 COMMENT 'Ngưỡng cảnh báo cần nhập thêm',
    updated_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_inv_variant FOREIGN KEY (variant_id) REFERENCES product_variants(variant_id) ON DELETE CASCADE,
    CONSTRAINT fk_inv_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouses(warehouse_id),
    CONSTRAINT fk_inv_location FOREIGN KEY (location_id) REFERENCES warehouse_locations(location_id),
    UNIQUE KEY uq_inventory (variant_id, warehouse_id, location_id),
    INDEX idx_inv_reorder (quantity_available, reorder_level)
) ENGINE=InnoDB;

-- Lịch sử mọi biến động kho: nhập, xuất, chuyển kho, kiểm kê, huỷ hàng lỗi
CREATE TABLE stock_movements (
    movement_id      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    variant_id       INT UNSIGNED NOT NULL,
    warehouse_id     INT UNSIGNED NOT NULL,
    movement_type    ENUM('nhap_hang','xuat_ban','tra_hang_nhap_kho','dieu_chinh_kiem_ke','huy_hang_loi','chuyen_kho') NOT NULL,
    quantity         INT NOT NULL COMMENT 'Số dương = nhập thêm, số âm = xuất bớt',
    reference_type   ENUM('purchase_order','order','return','stocktake','manual') NOT NULL,
    reference_id     BIGINT UNSIGNED NULL COMMENT 'ID của đơn nhập/đơn bán/phiếu liên quan',
    note             VARCHAR(255) NULL,
    created_by       INT UNSIGNED NULL COMMENT 'employee_id thực hiện',
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mv_variant FOREIGN KEY (variant_id) REFERENCES product_variants(variant_id),
    CONSTRAINT fk_mv_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouses(warehouse_id),
    INDEX idx_mv_variant_time (variant_id, created_at),
    INDEX idx_mv_reference (reference_type, reference_id)
) ENGINE=InnoDB;

-- Kiểm kê định kỳ
CREATE TABLE stocktakes (
    stocktake_id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    warehouse_id     INT UNSIGNED NOT NULL,
    status           ENUM('dang_kiem','hoan_thanh') NOT NULL DEFAULT 'dang_kiem',
    started_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at     DATETIME NULL,
    created_by       INT UNSIGNED NULL,
    CONSTRAINT fk_st_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouses(warehouse_id)
) ENGINE=InnoDB;

CREATE TABLE stocktake_items (
    stocktake_item_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    stocktake_id      INT UNSIGNED NOT NULL,
    variant_id        INT UNSIGNED NOT NULL,
    system_quantity   INT NOT NULL COMMENT 'Số hệ thống ghi nhận',
    counted_quantity  INT NOT NULL COMMENT 'Số đếm thực tế',
    difference        INT GENERATED ALWAYS AS (counted_quantity - system_quantity) STORED,
    note              VARCHAR(255) NULL,
    CONSTRAINT fk_sti_stocktake FOREIGN KEY (stocktake_id) REFERENCES stocktakes(stocktake_id) ON DELETE CASCADE,
    CONSTRAINT fk_sti_variant FOREIGN KEY (variant_id) REFERENCES product_variants(variant_id)
) ENGINE=InnoDB;

-- =====================================================================
-- 4. NHÀ CUNG CẤP & NHẬP HÀNG
-- =====================================================================

CREATE TABLE suppliers (
    supplier_id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name             VARCHAR(200) NOT NULL,
    contact_person   VARCHAR(150) NULL,
    phone            VARCHAR(20) NULL,
    email            VARCHAR(150) NULL,
    address          VARCHAR(255) NULL
) ENGINE=InnoDB;

CREATE TABLE purchase_orders (
    purchase_order_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    po_code            VARCHAR(30) NOT NULL UNIQUE,
    supplier_id        INT UNSIGNED NOT NULL,
    warehouse_id       INT UNSIGNED NOT NULL,
    status             ENUM('draft','ordered','partial_received','received','cancelled') NOT NULL DEFAULT 'draft',
    order_date         DATE NOT NULL,
    expected_date      DATE NULL,
    received_date      DATE NULL,
    total_amount       DECIMAL(14,0) NOT NULL DEFAULT 0,
    created_by         INT UNSIGNED NULL,
    CONSTRAINT fk_po_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id),
    CONSTRAINT fk_po_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouses(warehouse_id)
) ENGINE=InnoDB;

CREATE TABLE purchase_order_items (
    po_item_id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_order_id  INT UNSIGNED NOT NULL,
    variant_id         INT UNSIGNED NOT NULL,
    quantity_ordered   INT NOT NULL,
    quantity_received  INT NOT NULL DEFAULT 0,
    unit_cost          DECIMAL(12,0) NOT NULL,
    CONSTRAINT fk_poi_po FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(purchase_order_id) ON DELETE CASCADE,
    CONSTRAINT fk_poi_variant FOREIGN KEY (variant_id) REFERENCES product_variants(variant_id)
) ENGINE=InnoDB;

-- =====================================================================
-- 5. KHÁCH HÀNG
-- =====================================================================

CREATE TABLE customers (
    customer_id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name        VARCHAR(150) NOT NULL,
    email            VARCHAR(150) NULL UNIQUE,
    phone            VARCHAR(20) NOT NULL UNIQUE,
    password_hash    VARCHAR(255) NULL,
    gender           ENUM('nam','nu','khac') NULL,
    date_of_birth    DATE NULL,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE customer_addresses (
    address_id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id      INT UNSIGNED NOT NULL,
    recipient_name   VARCHAR(150) NOT NULL,
    phone            VARCHAR(20) NOT NULL,
    province         VARCHAR(100) NOT NULL,
    district         VARCHAR(100) NOT NULL,
    ward             VARCHAR(100) NOT NULL,
    address_detail   VARCHAR(255) NOT NULL COMMENT 'Số nhà, tên đường',
    address_type     ENUM('nha_rieng','van_phong') NOT NULL DEFAULT 'nha_rieng',
    is_default       TINYINT(1) NOT NULL DEFAULT 0,
    CONSTRAINT fk_addr_customer FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- 6. NHÂN VIÊN
-- =====================================================================

CREATE TABLE roles (
    role_id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name             VARCHAR(50) NOT NULL UNIQUE COMMENT 'VD: Quản lý kho, Nhân viên bán hàng, Admin'
) ENGINE=InnoDB;

CREATE TABLE employees (
    employee_id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name        VARCHAR(150) NOT NULL,
    email            VARCHAR(150) NOT NULL UNIQUE,
    phone            VARCHAR(20) NULL,
    role_id          INT UNSIGNED NOT NULL,
    warehouse_id     INT UNSIGNED NULL,
    status           ENUM('active','inactive') NOT NULL DEFAULT 'active',
    CONSTRAINT fk_emp_role FOREIGN KEY (role_id) REFERENCES roles(role_id),
    CONSTRAINT fk_emp_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouses(warehouse_id)
) ENGINE=InnoDB;

-- =====================================================================
-- 7. KHUYẾN MÃI
-- =====================================================================

CREATE TABLE discounts (
    discount_id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code              VARCHAR(50) NOT NULL UNIQUE,
    type              ENUM('percent','fixed') NOT NULL,
    value             DECIMAL(12,0) NOT NULL,
    min_order_amount  DECIMAL(12,0) NOT NULL DEFAULT 0,
    max_discount      DECIMAL(12,0) NULL COMMENT 'Giới hạn tối đa nếu type=percent',
    start_date        DATETIME NOT NULL,
    end_date          DATETIME NOT NULL,
    usage_limit       INT UNSIGNED NULL,
    used_count        INT UNSIGNED NOT NULL DEFAULT 0,
    is_active         TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- =====================================================================
-- 8. ĐƠN HÀNG
-- =====================================================================

CREATE TABLE orders (
    order_id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_code         VARCHAR(30) NOT NULL UNIQUE,
    customer_id        INT UNSIGNED NOT NULL,
    warehouse_id       INT UNSIGNED NOT NULL COMMENT 'Kho xuất hàng',
    shipping_address_id INT UNSIGNED NOT NULL,
    discount_id        INT UNSIGNED NULL,
    order_date         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status             ENUM('cho_xac_nhan','da_xac_nhan','dang_dong_goi','da_giao_van_chuyen',
                             'dang_giao','da_giao_thanh_cong','giao_that_bai','da_huy','da_tra_hang')
                         NOT NULL DEFAULT 'cho_xac_nhan',
    subtotal_amount    DECIMAL(14,0) NOT NULL COMMENT 'Tổng tiền hàng trước giảm giá',
    discount_amount    DECIMAL(14,0) NOT NULL DEFAULT 0,
    shipping_fee       DECIMAL(12,0) NOT NULL DEFAULT 0,
    total_amount       DECIMAL(14,0) NOT NULL COMMENT 'subtotal - discount + shipping_fee',
    payment_method     ENUM('cod','chuyen_khoan','the_tin_dung','vi_dien_tu') NOT NULL,
    payment_status     ENUM('chua_thanh_toan','da_thanh_toan','hoan_tien') NOT NULL DEFAULT 'chua_thanh_toan',
    note               VARCHAR(500) NULL,
    created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_order_customer FOREIGN KEY (customer_id) REFERENCES customers(customer_id),
    CONSTRAINT fk_order_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouses(warehouse_id),
    CONSTRAINT fk_order_address FOREIGN KEY (shipping_address_id) REFERENCES customer_addresses(address_id),
    CONSTRAINT fk_order_discount FOREIGN KEY (discount_id) REFERENCES discounts(discount_id),
    INDEX idx_order_status (status),
    INDEX idx_order_customer (customer_id)
) ENGINE=InnoDB;

CREATE TABLE order_items (
    order_item_id      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id           BIGINT UNSIGNED NOT NULL,
    variant_id         INT UNSIGNED NOT NULL,
    quantity           INT NOT NULL,
    unit_price         DECIMAL(12,0) NOT NULL COMMENT 'Giá tại thời điểm bán',
    line_discount      DECIMAL(12,0) NOT NULL DEFAULT 0,
    line_total         DECIMAL(14,0) GENERATED ALWAYS AS (quantity * unit_price - line_discount) STORED,
    CONSTRAINT fk_oi_order FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    CONSTRAINT fk_oi_variant FOREIGN KEY (variant_id) REFERENCES product_variants(variant_id)
) ENGINE=InnoDB;

-- =====================================================================
-- 9. THANH TOÁN
-- =====================================================================

CREATE TABLE payments (
    payment_id        BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id          BIGINT UNSIGNED NOT NULL,
    method            ENUM('cod','chuyen_khoan','the_tin_dung','vi_dien_tu') NOT NULL,
    amount            DECIMAL(14,0) NOT NULL,
    status             ENUM('cho_xu_ly','thanh_cong','that_bai','hoan_tien') NOT NULL DEFAULT 'cho_xu_ly',
    transaction_code  VARCHAR(100) NULL,
    paid_at           DATETIME NULL,
    CONSTRAINT fk_pay_order FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- 10. GIAO HÀNG / VẬN CHUYỂN (chi tiết đầy đủ)
-- =====================================================================

-- Đơn vị vận chuyển (GHN, GHTK, Viettel Post, tự vận chuyển...)
CREATE TABLE shipping_carriers (
    carrier_id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name               VARCHAR(100) NOT NULL,
    code               VARCHAR(30) NOT NULL UNIQUE,
    hotline            VARCHAR(20) NULL,
    tracking_url_template VARCHAR(255) NULL COMMENT 'VD: https://tracking.ghn.vn/?code={tracking_code}',
    is_active          TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- Phương thức giao hàng (tiêu chuẩn/nhanh/hoả tốc) theo từng carrier
CREATE TABLE shipping_methods (
    shipping_method_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    carrier_id         INT UNSIGNED NOT NULL,
    name               VARCHAR(100) NOT NULL COMMENT 'VD: Giao tiêu chuẩn, Giao nhanh, Hoả tốc',
    base_fee           DECIMAL(12,0) NOT NULL,
    fee_per_kg         DECIMAL(12,0) NOT NULL DEFAULT 0,
    estimated_days_min TINYINT UNSIGNED NOT NULL,
    estimated_days_max TINYINT UNSIGNED NOT NULL,
    CONSTRAINT fk_sm_carrier FOREIGN KEY (carrier_id) REFERENCES shipping_carriers(carrier_id)
) ENGINE=InnoDB;

-- Vận đơn: 1 đơn hàng có thể tách thành nhiều vận đơn (giao nhiều đợt)
CREATE TABLE shipments (
    shipment_id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id            BIGINT UNSIGNED NOT NULL,
    carrier_id          INT UNSIGNED NOT NULL,
    shipping_method_id  INT UNSIGNED NOT NULL,
    warehouse_id        INT UNSIGNED NOT NULL COMMENT 'Kho xuất phát',
    tracking_code       VARCHAR(100) NOT NULL UNIQUE,
    -- Thông tin người nhận snapshot tại thời điểm tạo vận đơn (đề phòng khách đổi địa chỉ sau)
    receiver_name        VARCHAR(150) NOT NULL,
    receiver_phone        VARCHAR(20) NOT NULL,
    receiver_address     VARCHAR(500) NOT NULL,
    total_weight_gram    INT UNSIGNED NOT NULL,
    shipping_fee         DECIMAL(12,0) NOT NULL,
    cod_amount           DECIMAL(14,0) NOT NULL DEFAULT 0 COMMENT 'Số tiền thu hộ COD, 0 nếu đã thanh toán trước',
    status                ENUM('cho_lay_hang','da_lay_hang','dang_van_chuyen','dang_giao',
                                'giao_thanh_cong','giao_that_bai','hoan_hang','da_huy')
                            NOT NULL DEFAULT 'cho_lay_hang',
    picked_up_at          DATETIME NULL,
    delivered_at          DATETIME NULL,
    delivery_attempts     TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Số lần đã cố gắng giao',
    created_at             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ship_order FOREIGN KEY (order_id) REFERENCES orders(order_id),
    CONSTRAINT fk_ship_carrier FOREIGN KEY (carrier_id) REFERENCES shipping_carriers(carrier_id),
    CONSTRAINT fk_ship_method FOREIGN KEY (shipping_method_id) REFERENCES shipping_methods(shipping_method_id),
    CONSTRAINT fk_ship_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouses(warehouse_id),
    INDEX idx_ship_status (status),
    INDEX idx_ship_tracking (tracking_code)
) ENGINE=InnoDB;

-- Chi tiết sản phẩm nằm trong từng vận đơn (hỗ trợ tách đơn thành nhiều kiện)
CREATE TABLE shipment_items (
    shipment_item_id   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shipment_id        BIGINT UNSIGNED NOT NULL,
    order_item_id      BIGINT UNSIGNED NOT NULL,
    quantity           INT NOT NULL,
    CONSTRAINT fk_si_shipment FOREIGN KEY (shipment_id) REFERENCES shipments(shipment_id) ON DELETE CASCADE,
    CONSTRAINT fk_si_orderitem FOREIGN KEY (order_item_id) REFERENCES order_items(order_item_id)
) ENGINE=InnoDB;

-- Lịch sử trạng thái vận chuyển (tracking timeline hiển thị cho khách)
CREATE TABLE shipment_tracking_history (
    tracking_history_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shipment_id          BIGINT UNSIGNED NOT NULL,
    status                VARCHAR(100) NOT NULL COMMENT 'VD: Đã tiếp nhận, Đang trung chuyển, Đang giao...',
    location              VARCHAR(255) NULL COMMENT 'Bưu cục / khu vực hiện tại',
    description           VARCHAR(500) NULL,
    occurred_at           DATETIME NOT NULL,
    CONSTRAINT fk_th_shipment FOREIGN KEY (shipment_id) REFERENCES shipments(shipment_id) ON DELETE CASCADE,
    INDEX idx_th_shipment_time (shipment_id, occurred_at)
) ENGINE=InnoDB;

-- Chi tiết từng lần giao thất bại (thực tế rất hay xảy ra: khách không nghe máy, sai địa chỉ...)
CREATE TABLE delivery_attempts_log (
    attempt_id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shipment_id        BIGINT UNSIGNED NOT NULL,
    attempt_number     TINYINT UNSIGNED NOT NULL,
    attempted_at       DATETIME NOT NULL,
    result             ENUM('thanh_cong','khach_khong_nghe_may','sai_dia_chi','khach_hen_lai','khach_tu_choi_nhan')
                        NOT NULL,
    note               VARCHAR(255) NULL,
    CONSTRAINT fk_da_shipment FOREIGN KEY (shipment_id) REFERENCES shipments(shipment_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- 11. ĐỔI TRẢ HÀNG
-- =====================================================================

CREATE TABLE returns (
    return_id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id           BIGINT UNSIGNED NOT NULL,
    customer_id        INT UNSIGNED NOT NULL,
    return_type        ENUM('hoan_tien','doi_hang') NOT NULL,
    reason             VARCHAR(500) NOT NULL,
    status              ENUM('yeu_cau','dang_xu_ly','da_nhan_hang_hoan','hoan_thanh','tu_choi')
                         NOT NULL DEFAULT 'yeu_cau',
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ret_order FOREIGN KEY (order_id) REFERENCES orders(order_id),
    CONSTRAINT fk_ret_customer FOREIGN KEY (customer_id) REFERENCES customers(customer_id)
) ENGINE=InnoDB;

CREATE TABLE return_items (
    return_item_id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    return_id          INT UNSIGNED NOT NULL,
    order_item_id      BIGINT UNSIGNED NOT NULL,
    quantity           INT NOT NULL,
    item_condition      ENUM('con_moi','da_qua_su_dung','loi_do_san_xuat') NOT NULL,
    refund_amount       DECIMAL(12,0) NOT NULL DEFAULT 0,
    CONSTRAINT fk_ri_return FOREIGN KEY (return_id) REFERENCES returns(return_id) ON DELETE CASCADE,
    CONSTRAINT fk_ri_orderitem FOREIGN KEY (order_item_id) REFERENCES order_items(order_item_id)
) ENGINE=InnoDB;

-- =====================================================================
-- 12. ĐÁNH GIÁ SẢN PHẨM
-- =====================================================================

CREATE TABLE product_reviews (
    review_id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id         INT UNSIGNED NOT NULL,
    customer_id        INT UNSIGNED NOT NULL,
    order_item_id      BIGINT UNSIGNED NULL COMMENT 'Chỉ khách đã mua mới được review',
    rating             TINYINT UNSIGNED NOT NULL COMMENT '1-5 sao',
    comment            TEXT NULL,
    created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_rv_product FOREIGN KEY (product_id) REFERENCES products(product_id),
    CONSTRAINT fk_rv_customer FOREIGN KEY (customer_id) REFERENCES customers(customer_id),
    CONSTRAINT fk_rv_orderitem FOREIGN KEY (order_item_id) REFERENCES order_items(order_item_id),
    CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- DỮ LIỆU MẪU KHỞI TẠO (SEED CƠ BẢN)
-- =====================================================================

INSERT INTO warehouses (name, address, phone, manager_name) VALUES
('Kho tổng', '123 Đường Lê Văn Việt, Quận 9, TP.HCM', '0900000000', 'Nguyễn Văn Quản');

INSERT INTO attributes (name) VALUES ('Size'), ('Màu sắc'), ('Chất liệu');

INSERT INTO attribute_values (attribute_id, value) VALUES
(1,'S'),(1,'M'),(1,'L'),(1,'XL'),
(2,'Đen'),(2,'Trắng'),(2,'Xanh navy'),(2,'Đỏ'),
(3,'Cotton'),(3,'Kaki'),(3,'Jean');

INSERT INTO roles (name) VALUES ('Admin'),('Quản lý kho'),('Nhân viên bán hàng'),('Nhân viên giao hàng');

INSERT INTO shipping_carriers (name, code, hotline, tracking_url_template) VALUES
('Giao Hàng Nhanh', 'GHN', '1900636677', 'https://tracking.ghn.vn/?order_code={tracking_code}'),
('Giao Hàng Tiết Kiệm', 'GHTK', '1900636658', 'https://i.ghtk.vn/{tracking_code}'),
('Viettel Post', 'VTP', '1900545496', 'https://tracking.viettelpost.vn/{tracking_code}');

INSERT INTO shipping_methods (carrier_id, name, base_fee, fee_per_kg, estimated_days_min, estimated_days_max) VALUES
(1, 'Giao tiêu chuẩn', 20000, 5000, 2, 4),
(1, 'Giao nhanh', 35000, 7000, 1, 2),
(2, 'Giao tiêu chuẩn', 18000, 4500, 3, 5);