import React, { useEffect, useState } from "react";
import { api, money, num } from "../api";
import { ErrorBox } from "../components";
export default function Inventory() {
  const [rows, setRows] = useState([]),
    [warehouses, setWarehouses] = useState([]),
    [wid, setWid] = useState(""),
    [q, setQ] = useState(""),
    [error, setError] = useState("");
  useEffect(() => {
    api("/master/warehouses").then(setWarehouses);
  }, []);
  useEffect(() => {
    api(`/stocks?per_page=100&warehouse_id=${wid}&q=${encodeURIComponent(q)}`)
      .then((d) => setRows(d.data))
      .catch((e) => setError(e.message));
  }, [wid, q]);
  return (
    <>
      <div className="toolbar">
        <input
          className="search"
          placeholder="Search SKU, model, brand…"
          value={q}
          onChange={(e) => setQ(e.target.value)}
        />
        <select value={wid} onChange={(e) => setWid(e.target.value)}>
          <option value="">All warehouses</option>
          {warehouses.map((w) => (
            <option value={w.id} key={w.id}>
              {w.code} — {w.name}
            </option>
          ))}
        </select>
      </div>
      <ErrorBox error={error} />
      <div className="panel table-wrap">
        <table>
          <thead>
            <tr>
              <th>Warehouse</th>
              <th>SKU</th>
              <th>Product</th>
              <th>Tracking</th>
              <th className="right">On hand</th>
              <th className="right">Reserved</th>
              <th className="right">Available</th>
              <th className="right">Avg cost</th>
              <th>Health</th>
            </tr>
          </thead>
          <tbody>
            {rows.map((s) => {
              const av = Number(
                  s.available_quantity ??
                    Number(s.quantity) - Number(s.reserved_quantity),
                ),
                low = av <= Number(s.product?.min_stock);
              return (
                <tr key={s.id}>
                  <td>{s.warehouse?.code}</td>
                  <td>
                    <b>{s.product?.sku}</b>
                  </td>
                  <td>
                    {s.product?.name}
                    <div className="muted">
                      {s.product?.brand} {s.product?.model_code}
                    </div>
                  </td>
                  <td>{s.product?.is_serialized ? "Serial" : "Bulk"}</td>
                  <td className="right">{num(s.quantity)}</td>
                  <td className="right">
                    <b>{num(s.reserved_quantity)}</b>
                  </td>
                  <td className="right">
                    <b>{num(av)}</b>
                  </td>
                  <td className="right">{money(s.avg_cost)}</td>
                  <td>
                    <span
                      className={
                        low ? "status s-cancelled" : "status s-received"
                      }
                    >
                      {low ? "LOW" : "OK"}
                    </span>
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>
    </>
  );
}
