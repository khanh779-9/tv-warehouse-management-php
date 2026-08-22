import React, { useEffect, useState } from "react";
import { api, money, num } from "../api";
import { ErrorBox, Modal, Status } from "../components";
export default function Orders({ type }) {
  const purchase = type === "purchase",
    base = purchase ? "/purchase-orders" : "/sales-orders";
  const [rows, setRows] = useState([]),
    [products, setProducts] = useState([]),
    [partners, setPartners] = useState([]),
    [warehouses, setWarehouses] = useState([]),
    [modal, setModal] = useState(false),
    [detail, setDetail] = useState(null),
    [serialText, setSerialText] = useState({}),
    [error, setError] = useState("");
  const today = new Date().toISOString().slice(0, 10);
  const [form, setForm] = useState({
    partner_id: "",
    warehouse_id: "",
    ordered_at: today,
    channel: "DEALER",
    external_reference: "",
    notes: "",
    items: [{ product_id: "", quantity: 1, price: 0 }],
  });
  const load = () => {
    api(base)
      .then((d) => setRows(d.data))
      .catch((e) => setError(e.message));
  };
  useEffect(() => {
    load();
  }, [base]);
  useEffect(() => {
    api("/products?per_page=100").then((d) => setProducts(d.data));
    api("/master/warehouses").then(setWarehouses);
    api("/master/" + (purchase ? "suppliers" : "customers")).then(setPartners);
  }, [purchase]);
  const updateItem = (i, k, v) =>
    setForm((f) => ({
      ...f,
      items: f.items.map((x, n) => (n === i ? { ...x, [k]: v } : x)),
    }));
  const save = async (e) => {
    e.preventDefault();
    try {
      const body = {
        warehouse_id: Number(form.warehouse_id),
        ordered_at: form.ordered_at,
        notes: form.notes,
        items: form.items.map((i) => ({
          product_id: Number(i.product_id),
          quantity: Number(i.quantity),
          [purchase ? "unit_cost" : "unit_price"]: Number(i.price),
        })),
        [purchase ? "supplier_id" : "customer_id"]: form.partner_id
          ? Number(form.partner_id)
          : null,
        ...(!purchase
          ? {
              channel: form.channel,
              external_reference: form.external_reference || null,
            }
          : {}),
      };
      await api(base, { method: "POST", body: JSON.stringify(body) });
      setModal(false);
      load();
    } catch (e) {
      setError(e.message);
    }
  };
  const open = async (id) => {
    try {
      setSerialText({});
      setDetail(await api(base + "/" + id));
    } catch (e) {
      setError(e.message);
    }
  };
  const approve = async () => {
    try {
      await api(`${base}/${detail.id}/approve`, { method: "POST" });
      await open(detail.id);
      load();
    } catch (e) {
      setError(e.message);
    }
  };
  const process = async () => {
    try {
      const payload = [];
      for (const i of detail.items) {
        const remaining =
          Number(i.quantity) -
          Number(purchase ? i.received_quantity : i.issued_quantity);
        if (remaining <= 0) continue;
        const row = { item_id: i.id, quantity: remaining };
        if (i.product?.is_serialized) {
          if (purchase) {
            const lines = (serialText[i.id] || "")
              .split("\n")
              .map((x) => x.trim())
              .filter(Boolean);
            if (lines.length !== Math.round(remaining))
              throw new Error(
                `${i.product.sku}: enter exactly ${Math.round(remaining)} TV serial numbers, one per line.`,
              );
            row.serials = lines.map((serial_number) => ({ serial_number }));
          } else {
            let codes = (serialText[i.id] || "")
                .split("\n")
                .map((x) => x.trim())
                .filter(Boolean),
              ids = [];
            if (codes.length) {
              for (const code of codes) {
                const s = await api(
                  "/serials/lookup?code=" + encodeURIComponent(code),
                );
                ids.push(s.id);
              }
            } else {
              const d = await api(
                `/serials?per_page=100&product_id=${i.product_id}&warehouse_id=${detail.warehouse_id}&status=IN_STOCK`,
              );
              ids = d.data.slice(0, Math.round(remaining)).map((s) => s.id);
            }
            if (ids.length !== Math.round(remaining))
              throw new Error(
                `${i.product.sku}: need ${Math.round(remaining)} available TV serial units.`,
              );
            row.serial_ids = ids;
          }
        }
        payload.push(row);
      }
      await api(`${base}/${detail.id}/${purchase ? "receive" : "issue"}`, {
        method: "POST",
        body: JSON.stringify({ items: payload }),
      });
      setDetail(null);
      load();
    } catch (e) {
      setError(e.message);
    }
  };
  const cancel = async () => {
    try {
      await api(`${base}/${detail.id}/cancel`, { method: "POST" });
      setDetail(null);
      load();
    } catch (e) {
      setError(e.message);
    }
  };
  return (
    <>
      <div className="toolbar">
        <div className="muted">
          {purchase
            ? "Inbound procurement with manager approval and serialized receiving"
            : "Dealer/store/e-commerce fulfillment with stock reservation"}
        </div>
        <button onClick={() => setModal(true)}>
          + New {purchase ? "PO" : "SO"}
        </button>
      </div>
      <ErrorBox error={error} />
      <div className="panel table-wrap">
        <table>
          <thead>
            <tr>
              <th>Number</th>
              <th>{purchase ? "Supplier" : "Customer"}</th>
              <th>Warehouse</th>
              {!purchase && <th>Channel</th>}
              <th>Date</th>
              <th>Items</th>
              <th>Status</th>
              {purchase && <th>Approval</th>}
              <th></th>
            </tr>
          </thead>
          <tbody>
            {rows.map((o) => (
              <tr key={o.id}>
                <td>
                  <b>{purchase ? o.po_number : o.so_number}</b>
                </td>
                <td>
                  {purchase ? o.supplier?.name : o.customer?.name || "Walk-in"}
                </td>
                <td>{o.warehouse?.code}</td>
                {!purchase && <td>{o.channel}</td>}
                <td>{String(o.ordered_at).slice(0, 10)}</td>
                <td>{o.items_count}</td>
                <td>
                  <Status value={o.status} />
                </td>
                {purchase && (
                  <td>
                    <Status value={o.approval_status} />
                  </td>
                )}
                <td>
                  <button className="ghost" onClick={() => open(o.id)}>
                    Open
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      {modal && (
        <Modal
          title={`New ${purchase ? "purchase" : "sales"} order`}
          onClose={() => setModal(false)}
        >
          <form onSubmit={save} className="form-grid">
            <label>
              {purchase ? "Supplier" : "Dealer / Customer"}
              <select
                value={form.partner_id}
                onChange={(e) =>
                  setForm({ ...form, partner_id: e.target.value })
                }
                required={purchase}
              >
                <option value="">
                  {purchase ? "Select supplier" : "Walk-in / select"}
                </option>
                {partners.map((p) => (
                  <option key={p.id} value={p.id}>
                    {p.code} — {p.name}
                  </option>
                ))}
              </select>
            </label>
            <label>
              Warehouse
              <select
                required
                value={form.warehouse_id}
                onChange={(e) =>
                  setForm({ ...form, warehouse_id: e.target.value })
                }
              >
                <option value="">Select warehouse</option>
                {warehouses.map((w) => (
                  <option key={w.id} value={w.id}>
                    {w.code}
                  </option>
                ))}
              </select>
            </label>
            {!purchase && (
              <>
                <label>
                  Sales channel
                  <select
                    value={form.channel}
                    onChange={(e) =>
                      setForm({ ...form, channel: e.target.value })
                    }
                  >
                    {["DEALER", "RETAIL_STORE", "ECOMMERCE", "INTERNAL"].map(
                      (x) => (
                        <option key={x}>{x}</option>
                      ),
                    )}
                  </select>
                </label>
                <label>
                  External reference
                  <input
                    placeholder="Dealer PO / marketplace order"
                    value={form.external_reference}
                    onChange={(e) =>
                      setForm({ ...form, external_reference: e.target.value })
                    }
                  />
                </label>
              </>
            )}
            <label>
              Order date
              <input
                type="date"
                value={form.ordered_at}
                onChange={(e) =>
                  setForm({ ...form, ordered_at: e.target.value })
                }
              />
            </label>
            <label className="span2">
              Notes
              <input
                value={form.notes}
                onChange={(e) => setForm({ ...form, notes: e.target.value })}
              />
            </label>
            <div className="span2 order-lines">
              <div className="line-head">
                <b>Line items</b>
                <button
                  type="button"
                  className="ghost"
                  onClick={() =>
                    setForm({
                      ...form,
                      items: [
                        ...form.items,
                        { product_id: "", quantity: 1, price: 0 },
                      ],
                    })
                  }
                >
                  + Add line
                </button>
              </div>
              {form.items.map((i, n) => (
                <div className="line" key={n}>
                  <select
                    required
                    value={i.product_id}
                    onChange={(e) => {
                      const id = e.target.value,
                        p = products.find((x) => String(x.id) === id);
                      updateItem(n, "product_id", id);
                      if (p)
                        updateItem(
                          n,
                          "price",
                          purchase ? p.cost_price : p.selling_price,
                        );
                    }}
                  >
                    <option value="">Product</option>
                    {products.map((p) => (
                      <option key={p.id} value={p.id}>
                        {p.sku} — {p.name}
                        {p.is_serialized ? " [Serial]" : ""}
                      </option>
                    ))}
                  </select>
                  <input
                    type="number"
                    step="0.001"
                    min="0.001"
                    value={i.quantity}
                    onChange={(e) => updateItem(n, "quantity", e.target.value)}
                  />
                  <input
                    type="number"
                    min="0"
                    value={i.price}
                    onChange={(e) => updateItem(n, "price", e.target.value)}
                  />
                </div>
              ))}
            </div>
            <div className="form-actions span2">
              <button
                type="button"
                className="secondary"
                onClick={() => setModal(false)}
              >
                Cancel
              </button>
              <button>Create {purchase ? "PO" : "SO & reserve stock"}</button>
            </div>
          </form>
        </Modal>
      )}
      {detail && (
        <Modal
          title={purchase ? detail.po_number : detail.so_number}
          onClose={() => setDetail(null)}
        >
          <div className="detail-head">
            <Status value={detail.status} />
            {purchase && <Status value={detail.approval_status} />}
            <span>{detail.warehouse?.name}</span>
          </div>
          <div className="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>SKU</th>
                  <th>Product</th>
                  <th className="right">Ordered</th>
                  <th className="right">Processed</th>
                  <th>Serial handling</th>
                </tr>
              </thead>
              <tbody>
                {detail.items.map((i) => {
                  const remaining =
                    Number(i.quantity) -
                    Number(purchase ? i.received_quantity : i.issued_quantity);
                  return (
                    <tr key={i.id}>
                      <td>{i.product?.sku}</td>
                      <td>{i.product?.name}</td>
                      <td className="right">{num(i.quantity)}</td>
                      <td className="right">
                        {num(
                          purchase ? i.received_quantity : i.issued_quantity,
                        )}
                      </td>
                      <td>
                        {i.product?.is_serialized && remaining > 0 ? (
                          <textarea
                            className="serial-box"
                            placeholder={
                              purchase
                                ? `One TV serial per line`
                                : `TV serial per line\n(blank = auto-pick demo)`
                            }
                            value={serialText[i.id] || ""}
                            onChange={(e) =>
                              setSerialText({
                                ...serialText,
                                [i.id]: e.target.value,
                              })
                            }
                          />
                        ) : (
                          <span className="muted">
                            {i.product?.is_serialized
                              ? "Done"
                              : "Quantity based"}
                          </span>
                        )}
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
          <div className="form-actions">
            {purchase && detail.approval_status === "PENDING" && (
              <button className="secondary" onClick={approve}>
                Manager approve
              </button>
            )}
            {!purchase &&
              !["COMPLETED", "CANCELLED"].includes(detail.status) && (
                <button className="secondary" onClick={cancel}>
                  Cancel & release reservation
                </button>
              )}
            {!["RECEIVED", "COMPLETED", "CANCELLED"].includes(detail.status) &&
              (!purchase || detail.approval_status === "APPROVED") && (
                <button onClick={process}>
                  {purchase
                    ? "Receive remaining goods"
                    : "Issue reserved stock"}
                </button>
              )}
          </div>
        </Modal>
      )}
    </>
  );
}
