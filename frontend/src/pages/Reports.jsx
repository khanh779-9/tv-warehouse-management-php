import React, { useEffect, useState } from "react";
import { api, money, num } from "../api";
import { ErrorBox } from "../components";
export default function Reports() {
  const [valuation, setValuation] = useState([]),
    [summary, setSummary] = useState([]),
    [TVs, setTVs] = useState([]),
    [error, setError] = useState("");
  useEffect(() => {
    api("/reports/valuation")
      .then(setValuation)
      .catch((e) => setError(e.message));
    api("/reports/movement-summary")
      .then(setSummary)
      .catch((e) => setError(e.message));
    api("/reports/device-status")
      .then(setTVs)
      .catch((e) => setError(e.message));
  }, []);
  const total = valuation.reduce((s, x) => s + Number(x.value), 0);
  return (
    <>
      <ErrorBox error={error} />
      <div className="metrics">
        <div className="metric">
          <span>Total inventory value</span>
          <strong>{money(total)}</strong>
          <small>Weighted average cost</small>
        </div>
        {summary.map((s) => (
          <div className="metric" key={s.type}>
            <span>{s.type}</span>
            <strong>{num(s.total_quantity)}</strong>
            <small>
              {s.movement_count} movements · {money(s.total_value)}
            </small>
          </div>
        ))}
        {TVs.map((s) => (
          <div className="metric" key={s.status + s.condition}>
            <span>
              {s.status} · {s.condition}
            </span>
            <strong>{s.device_count}</strong>
            <small>Serialized TVs</small>
          </div>
        ))}
      </div>
      <div className="panel table-wrap">
        <div className="panel-title">
          <h3>Stock valuation & availability</h3>
          <span className="muted">
            On hand, reservation and inventory value
          </span>
        </div>
        <table>
          <thead>
            <tr>
              <th>Warehouse</th>
              <th>SKU</th>
              <th>Product</th>
              <th className="right">On hand</th>
              <th className="right">Reserved</th>
              <th className="right">Available</th>
              <th className="right">Value</th>
            </tr>
          </thead>
          <tbody>
            {valuation.map((r, i) => (
              <tr key={i}>
                <td>{r.warehouse}</td>
                <td>{r.sku}</td>
                <td>{r.name}</td>
                <td className="right">{num(r.quantity)}</td>
                <td className="right">{num(r.reserved_quantity)}</td>
                <td className="right">
                  <b>{num(r.available_quantity)}</b>
                </td>
                <td className="right">
                  <b>{money(r.value)}</b>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </>
  );
}
