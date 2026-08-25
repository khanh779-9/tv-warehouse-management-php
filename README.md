# TV Warehouse Management System -- Laravel + React + MySQL

Portfolio full-stack project focused **only on television warehouse/distribution operations**. The business flows are designed to resemble realistic internal warehouse/logistics software for a consumer-electronics company: procurement, dealer/store fulfillment, per-TV serial tracking, stock reservation, inter-warehouse transfer, returns, service/warranty and audit history. All brands, models and companies in seed data are fictional. This project is not affiliated with OPPO or any manufacturer.

## Stack

- Backend: Laravel REST API + Sanctum
- Frontend: React + Vite + pnpm
- Database: MySQL 8
- Local infra: Docker Compose

## TV-specific domain model

Every SKU is a **TV model**, and every physical TV is individually traceable by a unique `serial_number`.

Each TV SKU stores:

- Brand and model code
- Screen size in inches
- Resolution (`Full HD`, `4K UHD`, `8K UHD`)
- Panel type (`LED`, `QLED`, `Mini LED`, `OLED`)
- Operating system / smart-TV platform
- Refresh rate
- Color
- Warranty duration
- Flexible JSON technical specs for HDR, HDMI ports, VRR, speakers, local dimming, etc.

No phone/IMEI, food batch or expiry-date logic is used.

## Business modules

### 1. TV catalog

- TV-only product catalog; backend forces `product_type = TV`.
- Every TV SKU is serialized.
- SKU, barcode, model code and selling/cost prices.
- Screen/panel/resolution/refresh-rate specification fields.
- Categories such as LED/4K, QLED/Mini LED and OLED.

### 2. Multi-warehouse inventory

- Distribution center, regional hub and service/return center.
- TV-size-oriented warehouse locations/bins.
- Per-warehouse stock balance.
- Weighted-average cost.
- `On Hand`, `Reserved`, `Available = On Hand - Reserved`.
- Low-stock checks use available quantity.

### 3. TV serial traceability

- Unique serial number for every physical TV.
- Current warehouse and location.
- Condition: `NEW`, `OPEN_BOX`, `RETURNED`, `REPAIR`, `DEFECTIVE`.
- Status: `IN_STOCK`, `SOLD`, `RETURNED`, `REPAIR`, `DEFECTIVE`.
- Links to inbound PO line and outbound SO line.
- Sold date and warranty period.
- Event history: received, transferred, sold, returned, warranty/service events.

### 4. Purchase Order / Goods Receipt

- Supplier + destination warehouse.
- Manager approval before receipt.
- Partial receipt.
- Receiving N TVs requires exactly N unique serial numbers.
- Aggregate stock and serial records are updated in the same transaction.

### 5. Dealer/store/e-commerce sales fulfillment

- Channels: `DEALER`, `RETAIL_STORE`, `ECOMMERCE`, `INTERNAL`.
- Confirmed orders reserve stock immediately.
- Atomic rollback if any order line cannot be reserved.
- Shipment selects the exact physical TV serials being issued.
- Shipment consumes both On Hand and Reserved quantities.
- Cancel releases unused reservations.

### 6. Inter-warehouse transfer

- Staff creates transfer request; manager/admin approves.
- Only Available stock can move.
- Physical TV serial records move with aggregate stock.
- Deterministic row-lock ordering reduces deadlock risk.

### 7. Stock count

- Physical/cycle count workflow.
- For serialized TVs, discrepancies must be reconciled by serial identity rather than anonymously changing only a number.

### 8. Returns

- Defect, DOA, wrong item, customer request, transport damage and other reasons.
- Returned TV does not immediately become saleable stock.
- Inspection disposition: `RESTOCK`, `REPAIR`, `SCRAP`.
- Restocked returns become `OPEN_BOX`.

### 9. Warranty / service

- Lookup by TV serial number.
- Warranty expiry check.
- Lifecycle: `RECEIVED`, `DIAGNOSING`, `REPAIRING`, `READY`, `COMPLETED`, `REJECTED`.
- Exact sold TV remains traceable through after-sales service.

### 10. Audit & reporting

- Business audit logs.
- Immutable stock-movement ledger.
- Inventory valuation and movement reports.
- Dashboard with reservations, low stock, open POs/SOs, warranty cases and returns.

## Concurrency / inventory consistency

Critical stock-changing operations use MySQL transactions and row locks:

```php
Stock::where('warehouse_id', $warehouseId)
    ->where('product_id', $productId)
    ->lockForUpdate()
    ->firstOrFail();
```

For a sales order:

```text
Create SO
   ↓
Create TV lines
   ↓
Lock stock rows
   ↓
Check Available = On Hand - Reserved
   ↓
Increase Reserved
   ↓
Create stock_reservations
   ↓
COMMIT
```

This demonstrates prevention of overselling under concurrent requests.

## Folder structure

```text
warehouse-management-portfolio/
├── backend/               # Laravel REST API
├── frontend/              # React + Vite + pnpm
├── template_db/
│   ├── data.sql           # CREATE DATABASE + schema + indexes + TV seed data
│   └── README.md
├── docker-compose.yml
└── README.md
```

## Quick start

```bash
docker compose up -d mysql
```

Or import manually:

```bash
mysql -u root -p < template_db/data.sql
```

Laravel migrations + seeder alternative:

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Frontend:

```bash
cd frontend
corepack enable
pnpm install
cp .env.example .env
pnpm dev
```

Demo accounts use password `password`:

| Role    | Email                       |
| ------- | --------------------------- |
| Admin   | `admin@warehouse.local`   |
| Manager | `manager@warehouse.local` |
| Staff   | `staff@warehouse.local`   |
| Viewer  | `viewer@warehouse.local`  |

## API highlights

```text
GET    /api/stocks
GET    /api/serials
GET    /api/serials/lookup?code=<TV-serial-number>
GET    /api/reservations
POST   /api/purchase-orders
POST   /api/purchase-orders/{id}/approve
POST   /api/purchase-orders/{id}/receive
POST   /api/sales-orders
POST   /api/sales-orders/{id}/issue
POST   /api/sales-orders/{id}/cancel
POST   /api/transfers/{id}/approve
POST   /api/transfers/{id}/complete
POST   /api/returns
POST   /api/returns/{id}/inspect
POST   /api/warranty-claims
PUT    /api/warranty-claims/{id}
GET    /api/audit-logs
```

See `backend/API_EXAMPLES.md` for request examples.
