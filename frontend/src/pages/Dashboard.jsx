import React from "react";
import { money } from "../api";
import { Card, Empty, ErrorBox, useLoad } from "../components";
export default function Dashboard() {
  const { data, error, loading } = useLoad("/dashboard", []);
  if (loading) return <div className="skeleton">Loading dashboard…</div>;
  const s = data?.summary || {};
  return (
    <>
      <ErrorBox error={error} />
      <div className="metrics">
        <Card title="Active SKUs" value={s.products || 0} />
        <Card title="Warehouses" value={s.warehouses || 0} />
        <Card
          title="Serialized TVs"
          value={s.serialized_devices || 0}
          sub={`${s.devices_in_stock || 0} currently in stock`}
        />
        <Card
          title="Reserved units"
          value={s.active_reservations || 0}
          sub="Committed to sales orders"
        />
        <Card
          title="Low available stock"
          value={s.low_stock || 0}
          sub="After reservations"
        />
        <Card title="Inventory value" value={money(s.stock_value)} />
        <Card title="Open POs" value={s.open_purchase_orders || 0} />
        <Card title="Open SOs" value={s.open_sales_orders || 0} />
        <Card title="Warranty cases" value={s.open_warranty_claims || 0} />
        <Card
          title="Returns awaiting inspection"
          value={s.pending_returns || 0}
        />
      </div>
      <div className="panel">
        <div className="panel-title">
          <h3>Recent stock movements</h3>
          <span className="muted">Inventory ledger</span>
        </div>
        {data?.recent_movements?.length ? (
          <div className="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Time</th>
                  <th>Type</th>
                  <th>Warehouse</th>
                  <th>SKU</th>
                  <th>TV</th>
                  <th className="right">Qty</th>
                </tr>
              </thead>
              <tbody>
                {data.recent_movements.map((m) => (
                  <tr key={m.id}>
                    <td>{new Date(m.occurred_at).toLocaleString()}</td>
                    <td>
                      <span className="pill">{m.type}</span>
                    </td>
                    <td>{m.warehouse?.code}</td>
                    <td>{m.product?.sku}</td>
                    <td>{m.product?.name}</td>
                    <td className="right">{m.quantity}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        ) : (
          <Empty />
        )}
      </div>
    </>
  );
}
