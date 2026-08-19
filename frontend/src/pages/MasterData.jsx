import React, { useEffect, useState } from "react";
import { api } from "../api";
import { ErrorBox } from "../components";
const tabs = ["warehouses", "suppliers", "customers", "categories"];
export default function MasterData() {
  const [type, setType] = useState("warehouses"),
    [rows, setRows] = useState([]),
    [error, setError] = useState("");
  const load = () => {
    api("/master/" + type)
      .then(setRows)
      .catch((e) => setError(e.message));
  };
  useEffect(() => {
    load();
  }, [type]);
  const create = async () => {
    const name = prompt("Name");
    if (!name) return;
    const code = type === "categories" ? null : prompt("Code");
    try {
      await api("/master/" + type, {
        method: "POST",
        body: JSON.stringify(
          type === "categories" ? { name } : { name, code, is_active: true },
        ),
      });
      load();
    } catch (e) {
      setError(e.message);
    }
  };
  return (
    <>
      <div className="tabs">
        {tabs.map((t) => (
          <button
            className={t === type ? "active" : ""}
            onClick={() => setType(t)}
            key={t}
          >
            {t.replace("_", " ")}
          </button>
        ))}
        <button className="push" onClick={create}>
          + Add
        </button>
      </div>
      <ErrorBox error={error} />
      <div className="cards-list">
        {rows.map((r) => (
          <div className="entity-card" key={r.id}>
            <div>
              <small>{r.code || type.slice(0, -1).toUpperCase()}</small>
              <h3>{r.name}</h3>
              <p>
                {r.address ||
                  r.email ||
                  r.description ||
                  "No additional details"}
              </p>
            </div>
            {"is_active" in r && (
              <span
                className={
                  r.is_active ? "status s-received" : "status s-cancelled"
                }
              >
                {r.is_active ? "ACTIVE" : "INACTIVE"}
              </span>
            )}
          </div>
        ))}
      </div>
    </>
  );
}
