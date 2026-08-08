# API examples - TV Warehouse Management

All examples assume `Authorization: Bearer <token>` and JSON requests.

## Create a TV purchase order

```json
POST /api/purchase-orders
{
  "supplier_id": 1,
  "warehouse_id": 1,
  "ordered_at": "2026-08-25",
  "expected_at": "2026-08-30",
  "notes": "55-inch QLED replenishment",
  "items": [
    {"product_id": 3, "quantity": 4, "unit_cost": 10400000}
  ]
}
```

## Approve PO

```text
POST /api/purchase-orders/{id}/approve
```

## Receive serialized TVs

Each physical TV is tracked by its manufacturer serial number.

```json
POST /api/purchase-orders/{id}/receive
{
  "items": [
    {
      "item_id": 1,
      "quantity": 2,
      "serials": [
        {"serial_number": "TV-Q55-120-SN-09001", "warehouse_location_id": 2},
        {"serial_number": "TV-Q55-120-SN-09002", "warehouse_location_id": 2}
      ]
    }
  ]
}
```

## Lookup one physical TV

```text
GET /api/serials/lookup?code=TV-Q55-120-SN-09001
```

## Create a dealer sales order

```json
POST /api/sales-orders
{
  "customer_id": 1,
  "warehouse_id": 1,
  "channel": "DEALER",
  "external_reference": "DEALER-TV-PO-2026-099",
  "ordered_at": "2026-08-25",
  "items": [
    {"product_id": 3, "quantity": 2, "unit_price": 13990000}
  ]
}
```

Creating the order reserves available stock atomically.

## Issue reserved TVs by serial

```json
POST /api/sales-orders/{id}/issue
{
  "items": [
    {
      "item_id": 1,
      "quantity": 2,
      "serial_ids": [21, 22]
    }
  ]
}
```

## Warranty lookup / claim

```json
POST /api/warranty-claims
{
  "customer_id": 1,
  "serial_code": "TV-Q55-120-SOLD-00001",
  "issue_description": "TV powers on but intermittently loses image."
}
```
