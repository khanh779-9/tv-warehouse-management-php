import React, { useEffect, useState } from "react";
import { api, num } from "../api";
import { ErrorBox, Modal, Status } from "../components";
export default function Transfers() {
  const [rows, setRows] = useState([]),
    [warehouses, setWarehouses] = useState([]),
    [products, setProducts] = useState([]),
    [modal, setModal] = useState(false),
    [detail, setDetail] = useState(null),
    [error, setError] = useState("");
  const [form, setForm] = useState({
    from_warehouse_id: "",
    to_warehouse_id: "",
    notes: "",
    items: [{ product_id: "", quantity: 1 }],
  });
  const load = () => {
    api("/transfers")
      .then((d) => setRows(d.data))
      .catch((e) => setError(e.message));
  };
  useEffect(() => {
    load();
  }, []);
  useEffect(() => {
    api("/master/warehouses").then(setWarehouses);
    api("/products?per_page=100").then((d) => setProducts(d.data));
  }, []);
  const save = async (e) => {
    e.preventDefault();
    try {
      await api("/transfers", {
        method: "POST",
        body: JSON.stringify({
          ...form,
          from_warehouse_id: Number(form.from_warehouse_id),
          to_warehouse_id: Number(form.to_warehouse_id),
          items: form.items.map((i) => ({
            product_id: Number(i.product_id),
            quantity: Number(i.quantity),
          })),
        }),
      });
      setModal(false);
      load();
    } catch (e) {
      setError(e.message);
    }
  };
  const reloadDetail = async () =>
    setDetail(await api("/transfers/" + detail.id));
  const approve = async () => {
    try {
      await api(`/transfers/${detail.id}/approve`, { method: "POST" });
      await reloadDetail();
      load();
    } catch (e) {
      setError(e.message);
    }
  };
  const complete = async () => {
    try {
      await api(`/transfers/${detail.id}/complete`, { method: "POST" });
      setDetail(null);
      load();
    } catch (e) {
      setError(e.message);
    }
  };
  return (
    <>
      <div className="toolbar">
        <span className="muted">
          Inter-warehouse transfer with manager approval; serialized TVs move
          with their serial records
        </span>
        <button onClick={() => setModal(true)}>+ New transfer</button>
      </div>
      <ErrorBox error={error} />
      <div className="panel table-wrap">
        <table>
          <thead>
            <tr>
              <th>Transfer</th>
              <th>From</th>
              <th>To</th>
              <th>Items</th>
              <th>Status</th>
              <th>Approval</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            {rows.map((t) => (
              <tr key={t.id}>
                <td>
                  <b>{t.transfer_number}</b>
                </td>
                <td>{t.from_warehouse?.code}</td>
                <td>{t.to_warehouse?.code}</td>
                <td>{t.items_count}</td>
                <td>
                  <Status value={t.status} />
                </td>
                <td>
                  <Status value={t.approval_status} />
                </td>
                <td>
                  <button
                    className="ghost"
                    onClick={async () =>
                      setDetail(await api("/transfers/" + t.id))
                    }
                  >
                    Open
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      {modal && (
        <Modal title="New stock transfer" onClose={() => setModal(false)}>
          <form className="form-grid" onSubmit={save}>
            <label>
              From
              <select
                required
                value={form.from_warehouse_id}
                onChange={(e) =>
                  setForm({ ...form, from_warehouse_id: e.target.value })
                }
              >
                <option value="">Select</option>
                {warehouses.map((w) => (
                  <option key={w.id} value={w.id}>
                    {w.code}
                  </option>
                ))}
              </select>
            </label>
            <label>
              To
              <select
                required
                value={form.to_warehouse_id}
                onChange={(e) =>
                  setForm({ ...form, to_warehouse_id: e.target.value })
                }
              >
                <option value="">Select</option>
                {warehouses.map((w) => (
                  <option key={w.id} value={w.id}>
                    {w.code}
                  </option>
                ))}
              </select>
            </label>
            <label className="span2">
              Notes
              <input
                value={form.notes}
                onChange={(e) => setForm({ ...form, notes: e.target.value })}
              />
            </label>
            <div className="span2 order-lines">
              {form.items.map((i, n) => (
                <div className="line two" key={n}>
                  <select
                    value={i.product_id}
                    required
                    onChange={(e) =>
                      setForm((f) => ({
                        ...f,
                        items: f.items.map((x, k) =>
                          k === n ? { ...x, product_id: e.target.value } : x,
                        ),
                      }))
                    }
                  >
                    <option value="">Product</option>
                    {products.map((p) => (
                      <option value={p.id} key={p.id}>
                        {p.sku} — {p.name}
                        {p.is_serialized ? " [Serial]" : ""}
                      </option>
                    ))}
                  </select>
                  <input
                    type="number"
                    min="0.001"
                    step="0.001"
                    value={i.quantity}
                    onChange={(e) =>
                      setForm((f) => ({
                        ...f,
                        items: f.items.map((x, k) =>
                          k === n ? { ...x, quantity: e.target.value } : x,
                        ),
                      }))
                    }
                  />
                </div>
              ))}
              <button
                type="button"
                className="ghost"
                onClick={() =>
                  setForm({
                    ...form,
                    items: [...form.items, { product_id: "", quantity: 1 }],
                  })
                }
              >
                + Add product
              </button>
            </div>
            <div className="form-actions span2">
              <button>Create transfer request</button>
            </div>
          </form>
        </Modal>
      )}
      {detail && (
        <Modal title={detail.transfer_number} onClose={() => setDetail(null)}>
          <p>
            {detail.from_warehouse?.name} → {detail.to_warehouse?.name}
          </p>
          <div className="detail-head">
            <Status value={detail.status} />
            <Status value={detail.approval_status} />
          </div>
          {detail.items.map((i) => (
            <div className="line-row" key={i.id}>
              <span>
                {i.product?.sku} — {i.product?.name}
              </span>
              <b>{num(i.quantity)}</b>
            </div>
          ))}
          <div className="form-actions">
            {detail.approval_status === "PENDING" && (
              <button className="secondary" onClick={approve}>
                Manager approve
              </button>
            )}
            {detail.status === "DRAFT" &&
              detail.approval_status === "APPROVED" && (
                <button onClick={complete}>Complete transfer</button>
              )}
          </div>
        </Modal>
      )}
    </>
  );
}
