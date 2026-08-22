import React, { useEffect, useState } from "react";
import { api, num } from "../api";
import { ErrorBox, Status } from "../components";
export default function Reservations() {
  const [rows, setRows] = useState([]),
    [status, setStatus] = useState("ACTIVE"),
    [error, setError] = useState("");
  useEffect(() => {
    api("/reservations?per_page=100&status=" + status)
      .then((d) => setRows(d.data))
      .catch((e) => setError(e.message));
  }, [status]);
  return (
    <>
      <div className="toolbar">
        <div className="muted">
          Committed stock for dealer/store/e-commerce orders
        </div>
        <select value={status} onChange={(e) => setStatus(e.target.value)}>
          {["ACTIVE", "CONSUMED", "RELEASED"].map((x) => (
            <option key={x}>{x}</option>
          ))}
        </select>
      </div>
      <ErrorBox error={error} />
      <div className="panel table-wrap">
        <table>
          <thead>
            <tr>
              <th>Sales order</th>
              <th>Channel</th>
              <th>Warehouse</th>
              <th>SKU</th>
              <th>Product</th>
              <th className="right">Reserved</th>
              <th>Expires</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            {rows.map((r) => (
              <tr key={r.id}>
                <td>
                  <b>{r.sales_order?.so_number}</b>
                </td>
                <td>{r.sales_order?.channel}</td>
                <td>{r.warehouse?.code}</td>
                <td>{r.product?.sku}</td>
                <td>{r.product?.name}</td>
                <td className="right">
                  <b>{num(r.quantity)}</b>
                </td>
                <td>
                  {r.expires_at ? new Date(r.expires_at).toLocaleString() : "—"}
                </td>
                <td>
                  <Status value={r.status} />
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </>
  );
}
