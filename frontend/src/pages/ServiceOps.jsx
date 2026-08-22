import React, { useEffect, useState } from "react";
import { api } from "../api";
import { ErrorBox, Modal, Status } from "../components";
export default function ServiceOps() {
  const [claims, setClaims] = useState([]),
    [returns, setReturns] = useState([]),
    [lookup, setLookup] = useState(""),
    [TV, setTV] = useState(null),
    [modal, setModal] = useState(false),
    [error, setError] = useState("");
  const [form, setForm] = useState({
    customer_id: "",
    serial_code: "",
    issue_description: "",
  });
  const load = () => {
    api("/warranty-claims?per_page=100")
      .then((d) => setClaims(d.data))
      .catch((e) => setError(e.message));
    api("/returns?per_page=100")
      .then((d) => setReturns(d.data))
      .catch((e) => setError(e.message));
  };
  useEffect(() => {
    load();
  }, []);
  const doLookup = async () => {
    try {
      setTV(await api("/serials/lookup?code=" + encodeURIComponent(lookup)));
    } catch (e) {
      setError(e.message);
    }
  };
  const create = async (e) => {
    e.preventDefault();
    try {
      await api("/warranty-claims", {
        method: "POST",
        body: JSON.stringify({
          ...form,
          customer_id: form.customer_id || null,
        }),
      });
      setModal(false);
      setForm({ customer_id: "", serial_code: "", issue_description: "" });
      load();
    } catch (e) {
      setError(e.message);
    }
  };
  return (
    <>
      <ErrorBox error={error} />
      <div className="panel">
        <div className="panel-title">
          <h3>TV serial service lookup</h3>
          <button onClick={() => setModal(true)}>+ Warranty claim</button>
        </div>
        <div className="toolbar">
          <input
            className="search"
            placeholder="Enter TV serial number"
            value={lookup}
            onChange={(e) => setLookup(e.target.value)}
          />
          <button onClick={doLookup}>Lookup TV</button>
        </div>
        {TV && (
          <div className="info-grid">
            <div>
              <span>TV</span>
              <b>{TV.product?.name}</b>
            </div>
            <div>
              <span>Status</span>
              <b>{TV.status}</b>
            </div>
            <div>
              <span>Sold</span>
              <b>
                {TV.sold_at ? new Date(TV.sold_at).toLocaleDateString() : "—"}
              </b>
            </div>
            <div>
              <span>Warranty end</span>
              <b>{TV.warranty_end_at || "—"}</b>
            </div>
          </div>
        )}
      </div>
      <div className="panel table-wrap">
        <div className="panel-title">
          <h3>Warranty / service claims</h3>
          <span className="muted">After-sales traceability</span>
        </div>
        <table>
          <thead>
            <tr>
              <th>Claim</th>
              <th>TV</th>
              <th>Serial Number</th>
              <th>Issue</th>
              <th>Status</th>
              <th>Received</th>
            </tr>
          </thead>
          <tbody>
            {claims.map((c) => (
              <tr key={c.id}>
                <td>
                  <b>{c.claim_number}</b>
                </td>
                <td>{c.serial?.product?.name}</td>
                <td>{c.serial?.serial_number}</td>
                <td>{c.issue_description}</td>
                <td>
                  <Status value={c.status} />
                </td>
                <td>
                  {c.received_at
                    ? new Date(c.received_at).toLocaleDateString()
                    : "—"}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      <div className="panel table-wrap">
        <div className="panel-title">
          <h3>Customer returns</h3>
          <span className="muted">
            Returned TVs go through inspection before restock
          </span>
        </div>
        <table>
          <thead>
            <tr>
              <th>Return</th>
              <th>Customer</th>
              <th>Warehouse</th>
              <th>Reason</th>
              <th>Items</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            {returns.map((r) => (
              <tr key={r.id}>
                <td>
                  <b>{r.return_number}</b>
                </td>
                <td>{r.customer?.name || "—"}</td>
                <td>{r.warehouse?.code}</td>
                <td>{r.reason}</td>
                <td>{r.items_count}</td>
                <td>
                  <Status value={r.status} />
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      {modal && (
        <Modal title="Create warranty claim" onClose={() => setModal(false)}>
          <form className="form-grid" onSubmit={create}>
            <label className="span2">
              Serial Number
              <input
                required
                value={form.serial_code}
                onChange={(e) =>
                  setForm({ ...form, serial_code: e.target.value })
                }
              />
            </label>
            <label className="span2">
              Issue description
              <textarea
                required
                value={form.issue_description}
                onChange={(e) =>
                  setForm({ ...form, issue_description: e.target.value })
                }
              />
            </label>
            <div className="form-actions span2">
              <button>Create claim</button>
            </div>
          </form>
        </Modal>
      )}
    </>
  );
}
