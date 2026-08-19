import React, { useEffect, useState } from "react";
import { api, money, num } from "../api";
import { ErrorBox, Modal, Status } from "../components";
const blank = {
  sku: "",
  barcode: "",
  name: "",
  brand: "NovaVision",
  model_code: "",
  product_type: "TV",
  color: "Black",
  screen_size_inch: 55,
  resolution: "4K UHD",
  panel_type: "LED",
  operating_system: "Google TV",
  refresh_rate_hz: 60,
  is_serialized: true,
  warranty_months: 24,
  category_id: "",
  unit: "piece",
  cost_price: 0,
  selling_price: 0,
  min_stock: 0,
  description: "",
  is_active: true,
};
export default function Products() {
  const [rows, setRows] = useState([]),
    [cats, setCats] = useState([]),
    [q, setQ] = useState(""),
    [modal, setModal] = useState(false),
    [form, setForm] = useState(blank),
    [error, setError] = useState("");
  const load = () => {
    api("/products?per_page=100&q=" + encodeURIComponent(q))
      .then((d) => setRows(d.data))
      .catch((e) => setError(e.message));
  };
  useEffect(() => {
    load();
  }, [q]);
  useEffect(() => {
    api("/master/categories").then(setCats);
  }, []);
  const edit = (p) => {
    setForm({ ...blank, ...p, category_id: p.category_id || "" });
    setModal(true);
  };
  const save = async (e) => {
    e.preventDefault();
    setError("");
    try {
      const body = {
        ...form,
        product_type: "TV",
        is_serialized: true,
        category_id: form.category_id || null,
        screen_size_inch: Number(form.screen_size_inch),
        refresh_rate_hz: Number(form.refresh_rate_hz),
        cost_price: Number(form.cost_price),
        selling_price: Number(form.selling_price),
        min_stock: Number(form.min_stock),
        warranty_months: Number(form.warranty_months),
      };
      if (form.id)
        await api("/products/" + form.id, {
          method: "PUT",
          body: JSON.stringify(body),
        });
      else
        await api("/products", { method: "POST", body: JSON.stringify(body) });
      setModal(false);
      setForm(blank);
      load();
    } catch (e) {
      setError(e.message);
    }
  };
  return (
    <>
      <div className="toolbar">
        <input
          className="search"
          placeholder="Search TV SKU, model, brand, barcode…"
          value={q}
          onChange={(e) => setQ(e.target.value)}
        />
        <button
          onClick={() => {
            setForm(blank);
            setModal(true);
          }}
        >
          + New TV SKU
        </button>
      </div>
      <ErrorBox error={error} />
      <div className="panel table-wrap">
        <table>
          <thead>
            <tr>
              <th>SKU</th>
              <th>TV / Model</th>
              <th>Size</th>
              <th>Panel</th>
              <th>Resolution / Hz</th>
              <th>Tracking</th>
              <th className="right">Sell</th>
              <th className="right">Min</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            {rows.map((p) => (
              <tr key={p.id}>
                <td>
                  <b>{p.sku}</b>
                </td>
                <td>
                  <b>
                    {p.brand} {p.name}
                  </b>
                  <div className="muted">{p.model_code}</div>
                </td>
                <td>{p.screen_size_inch}"</td>
                <td>{p.panel_type}</td>
                <td>
                  {p.resolution} · {p.refresh_rate_hz}Hz
                </td>
                <td>
                  <Status value="SERIAL" />
                </td>
                <td className="right">{money(p.selling_price)}</td>
                <td className="right">{num(p.min_stock)}</td>
                <td>
                  <button className="ghost" onClick={() => edit(p)}>
                    Edit
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      {modal && (
        <Modal
          title={form.id ? "Edit TV SKU" : "New TV SKU"}
          onClose={() => setModal(false)}
        >
          <form className="form-grid" onSubmit={save}>
            <label>
              SKU
              <input
                required
                value={form.sku}
                onChange={(e) => setForm({ ...form, sku: e.target.value })}
              />
            </label>
            <label>
              Barcode
              <input
                value={form.barcode || ""}
                onChange={(e) => setForm({ ...form, barcode: e.target.value })}
              />
            </label>
            <label>
              Brand
              <input
                required
                value={form.brand || ""}
                onChange={(e) => setForm({ ...form, brand: e.target.value })}
              />
            </label>
            <label>
              Model code
              <input
                required
                value={form.model_code || ""}
                onChange={(e) =>
                  setForm({ ...form, model_code: e.target.value })
                }
              />
            </label>
            <label className="span2">
              TV name
              <input
                required
                value={form.name}
                onChange={(e) => setForm({ ...form, name: e.target.value })}
              />
            </label>
            <label>
              Category
              <select
                value={form.category_id}
                onChange={(e) =>
                  setForm({ ...form, category_id: e.target.value })
                }
              >
                <option value="">None</option>
                {cats.map((c) => (
                  <option key={c.id} value={c.id}>
                    {c.name}
                  </option>
                ))}
              </select>
            </label>
            <label>
              Color
              <input
                value={form.color || ""}
                onChange={(e) => setForm({ ...form, color: e.target.value })}
              />
            </label>
            <label>
              Screen size (inch)
              <input
                type="number"
                min="24"
                max="120"
                required
                value={form.screen_size_inch}
                onChange={(e) =>
                  setForm({ ...form, screen_size_inch: e.target.value })
                }
              />
            </label>
            <label>
              Resolution
              <select
                value={form.resolution}
                onChange={(e) =>
                  setForm({ ...form, resolution: e.target.value })
                }
              >
                {["Full HD", "4K UHD", "8K UHD"].map((x) => (
                  <option key={x}>{x}</option>
                ))}
              </select>
            </label>
            <label>
              Panel type
              <select
                value={form.panel_type}
                onChange={(e) =>
                  setForm({ ...form, panel_type: e.target.value })
                }
              >
                {["LED", "QLED", "Mini LED", "OLED"].map((x) => (
                  <option key={x}>{x}</option>
                ))}
              </select>
            </label>
            <label>
              Operating system
              <input
                placeholder="Google TV / Smart TV OS"
                value={form.operating_system || ""}
                onChange={(e) =>
                  setForm({ ...form, operating_system: e.target.value })
                }
              />
            </label>
            <label>
              Refresh rate (Hz)
              <input
                type="number"
                min="50"
                max="240"
                value={form.refresh_rate_hz}
                onChange={(e) =>
                  setForm({ ...form, refresh_rate_hz: e.target.value })
                }
              />
            </label>
            <label>
              Warranty (months)
              <input
                type="number"
                min="0"
                max="60"
                value={form.warranty_months}
                onChange={(e) =>
                  setForm({ ...form, warranty_months: e.target.value })
                }
              />
            </label>
            <label>
              Tracking
              <input value="Serial number per physical TV" disabled />
            </label>
            <label>
              Cost price
              <input
                type="number"
                min="0"
                value={form.cost_price}
                onChange={(e) =>
                  setForm({ ...form, cost_price: e.target.value })
                }
              />
            </label>
            <label>
              Selling price
              <input
                type="number"
                min="0"
                value={form.selling_price}
                onChange={(e) =>
                  setForm({ ...form, selling_price: e.target.value })
                }
              />
            </label>
            <label>
              Min available stock
              <input
                type="number"
                min="0"
                step="1"
                value={form.min_stock}
                onChange={(e) =>
                  setForm({ ...form, min_stock: e.target.value })
                }
              />
            </label>
            <label>
              Unit
              <input
                value={form.unit}
                onChange={(e) => setForm({ ...form, unit: e.target.value })}
              />
            </label>
            <label className="span2">
              Description
              <textarea
                value={form.description || ""}
                onChange={(e) =>
                  setForm({ ...form, description: e.target.value })
                }
              />
            </label>
            <div className="form-actions span2">
              <button
                type="button"
                className="secondary"
                onClick={() => setModal(false)}
              >
                Cancel
              </button>
              <button>Save</button>
            </div>
          </form>
        </Modal>
      )}
    </>
  );
}
