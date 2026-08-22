import React, { useEffect, useState } from "react";
import { api, num } from "../api";
import { ErrorBox, Modal, Status } from "../components";
export default function StockCounts() {
  const [rows, setRows] = useState([]),
    [warehouses, setWarehouses] = useState([]),
    [detail, setDetail] = useState(null),
    [error, setError] = useState("");
  const load = () => {
    api("/stock-counts")
      .then((d) => setRows(d.data))
      .catch((e) => setError(e.message));
  };
  useEffect(() => {
    load();
  }, []);
  useEffect(() => {
    api("/master/warehouses").then(setWarehouses);
  }, []);
  const create = async () => {
    const id = prompt(
      "Warehouse ID to count:\n" +
        warehouses.map((w) => `${w.id}: ${w.code} — ${w.name}`).join("\n"),
    );
    if (!id) return;
    try {
      await api("/stock-counts", {
        method: "POST",
        body: JSON.stringify({
          warehouse_id: Number(id),
          notes: "Cycle count",
        }),
      });
      load();
    } catch (e) {
      setError(e.message);
    }
  };
  const save = async () => {
    try {
      const d = await api(`/stock-counts/${detail.id}/items`, {
        method: "PUT",
        body: JSON.stringify({
          items: detail.items.map((i) => ({
            item_id: i.id,
            counted_quantity: Number(i.counted_quantity),
          })),
        }),
      });
      setDetail(d);
    } catch (e) {
      setError(e.message);
    }
  };
  const finalize = async () => {
    try {
      await save();
      await api(`/stock-counts/${detail.id}/finalize`, { method: "POST" });
      setDetail(null);
      load();
    } catch (e) {
      setError(e.message);
    }
  };
  return (
    <>
      <div className="toolbar">
        <span className="muted">Cycle count and reconciliation workflow</span>
        <button onClick={create}>+ Start stock count</button>
      </div>
      <ErrorBox error={error} />
      <div className="panel table-wrap">
        <table>
          <thead>
            <tr>
              <th>Count</th>
              <th>Warehouse</th>
              <th>Items</th>
              <th>Status</th>
              <th>Finalized</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            {rows.map((c) => (
              <tr key={c.id}>
                <td>
                  <b>{c.count_number}</b>
                </td>
                <td>{c.warehouse?.code}</td>
                <td>{c.items_count}</td>
                <td>
                  <Status value={c.status} />
                </td>
                <td>
                  {c.finalized_at
                    ? new Date(c.finalized_at).toLocaleString()
                    : "—"}
                </td>
                <td>
                  <button
                    className="ghost"
                    onClick={async () =>
                      setDetail(await api("/stock-counts/" + c.id))
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
      {detail && (
        <Modal title={detail.count_number} onClose={() => setDetail(null)}>
          <div className="table-wrap count-table">
            <table>
              <thead>
                <tr>
                  <th>SKU</th>
                  <th>Product</th>
                  <th className="right">System</th>
                  <th className="right">Counted</th>
                  <th className="right">Diff</th>
                </tr>
              </thead>
              <tbody>
                {detail.items.map((i, n) => (
                  <tr key={i.id}>
                    <td>{i.product?.sku}</td>
                    <td>{i.product?.name}</td>
                    <td className="right">{num(i.system_quantity)}</td>
                    <td className="right">
                      {detail.status === "DRAFT" ? (
                        <input
                          className="qty-input"
                          type="number"
                          min="0"
                          step="0.001"
                          value={i.counted_quantity}
                          onChange={(e) =>
                            setDetail((d) => ({
                              ...d,
                              items: d.items.map((x, k) =>
                                k === n
                                  ? {
                                      ...x,
                                      counted_quantity: e.target.value,
                                      difference:
                                        Number(e.target.value) -
                                        Number(x.system_quantity),
                                    }
                                  : x,
                              ),
                            }))
                          }
                        />
                      ) : (
                        num(i.counted_quantity)
                      )}
                    </td>
                    <td className="right">{num(i.difference)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          {detail.status === "DRAFT" && (
            <div className="form-actions">
              <button className="secondary" onClick={save}>
                Save draft
              </button>
              <button onClick={finalize}>Finalize & adjust</button>
            </div>
          )}
        </Modal>
      )}
    </>
  );
}
