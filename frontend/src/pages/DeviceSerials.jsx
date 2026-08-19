import React, { useEffect, useState } from "react";
import { api } from "../api";
import { ErrorBox, Modal, Status } from "../components";
export default function DeviceSerials() {
  const [rows, setRows] = useState([]),
    [q, setQ] = useState(""),
    [status, setStatus] = useState(""),
    [detail, setDetail] = useState(null),
    [error, setError] = useState("");
  const load = () => {
    api(`/serials?per_page=100&q=${encodeURIComponent(q)}&status=${status}`)
      .then((d) => setRows(d.data))
      .catch((e) => setError(e.message));
  };
  useEffect(() => {
    load();
  }, [q, status]);
  return (
    <>
      <div className="toolbar">
        <input
          className="search"
          placeholder="Scan/search TV serial number…"
          value={q}
          onChange={(e) => setQ(e.target.value)}
        />
        <select value={status} onChange={(e) => setStatus(e.target.value)}>
          <option value="">All statuses</option>
          {["IN_STOCK", "SOLD", "RETURNED", "REPAIR", "DEFECTIVE"].map((x) => (
            <option key={x}>{x}</option>
          ))}
        </select>
      </div>
      <ErrorBox error={error} />
      <div className="panel table-wrap">
        <table>
          <thead>
            <tr>
              <th>Serial Number</th>
              <th>TV</th>
              <th>Size / Panel</th>
              <th>Warehouse / Bin</th>
              <th>Condition</th>
              <th>Status</th>
              <th>Warranty</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            {rows.map((s) => (
              <tr key={s.id}>
                <td>
                  <b>{s.serial_number}</b>
                </td>
                <td>
                  {s.product?.name}
                  <div className="muted">{s.product?.model_code}</div>
                </td>
                <td>
                  {s.product?.screen_size_inch}" · {s.product?.panel_type}
                </td>
                <td>
                  {s.warehouse?.code || "—"}
                  {s.location?.code && (
                    <div className="muted">Bin {s.location.code}</div>
                  )}
                </td>
                <td>{s.condition}</td>
                <td>
                  <Status value={s.status} />
                </td>
                <td>{s.warranty_end_at || "—"}</td>
                <td>
                  <button
                    className="ghost"
                    onClick={async () =>
                      setDetail(await api("/serials/" + s.id))
                    }
                  >
                    Trace
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      {detail && (
        <Modal title="TV traceability" onClose={() => setDetail(null)}>
          <div className="detail-list">
            <p>
              <b>Serial:</b> {detail.serial_number}
            </p>
            <p>
              <b>TV:</b> {detail.product?.name}
            </p>
            <p>
              <b>Specification:</b> {detail.product?.screen_size_inch}" ·{" "}
              {detail.product?.resolution} · {detail.product?.panel_type} ·{" "}
              {detail.product?.refresh_rate_hz}Hz
            </p>
            <p>
              <b>Location:</b> {detail.warehouse?.name || "Not in warehouse"}{" "}
              {detail.location?.code ? `/ ${detail.location.code}` : ""}
            </p>
            <p>
              <b>Status:</b> {detail.status} · {detail.condition}
            </p>
            <p>
              <b>Received:</b>{" "}
              {detail.received_at
                ? new Date(detail.received_at).toLocaleString()
                : "—"}
            </p>
            <p>
              <b>Sold:</b>{" "}
              {detail.sold_at ? new Date(detail.sold_at).toLocaleString() : "—"}
            </p>
            <p>
              <b>Warranty:</b> {detail.warranty_start_at || "—"} →{" "}
              {detail.warranty_end_at || "—"}
            </p>
          </div>
          <div className="panel-title">
            <h3>TV event history</h3>
            <span className="muted">Unit-level traceability</span>
          </div>
          <div className="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Time</th>
                  <th>Event</th>
                  <th>From</th>
                  <th>To</th>
                  <th>Reference</th>
                  <th>User</th>
                </tr>
              </thead>
              <tbody>
                {(detail.events || []).map((e) => (
                  <tr key={e.id}>
                    <td>{new Date(e.occurred_at).toLocaleString()}</td>
                    <td>
                      <b>{e.event_type}</b>
                    </td>
                    <td>{e.from_warehouse?.code || "—"}</td>
                    <td>{e.to_warehouse?.code || "—"}</td>
                    <td>
                      {e.reference_type || "—"} {e.reference_id || ""}
                    </td>
                    <td>{e.user?.name || "System"}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </Modal>
      )}
    </>
  );
}
