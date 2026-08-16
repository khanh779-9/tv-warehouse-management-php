import React, { useEffect, useState } from "react";
import { api } from "./api";
export function Card({ title, value, sub }) {
  return (
    <div className="metric">
      <span>{title}</span>
      <strong>{value}</strong>
      {sub && <small>{sub}</small>}
    </div>
  );
}
export function Empty({ text = "No data yet" }) {
  return <div className="empty">{text}</div>;
}
export function Status({ value }) {
  return (
    <span className={`status s-${String(value).toLowerCase()}`}>{value}</span>
  );
}
export function useLoad(path, deps = []) {
  const [data, setData] = useState(null),
    [error, setError] = useState(""),
    [loading, setLoading] = useState(true);
  const load = () => {
    setLoading(true);
    api(path)
      .then(setData)
      .catch((e) => setError(e.message))
      .finally(() => setLoading(false));
  };
  useEffect(() => {
    load();
  }, deps);
  return { data, error, loading, reload: load };
}
export function Modal({ title, children, onClose }) {
  return (
    <div className="modal-backdrop" onMouseDown={onClose}>
      <div className="modal" onMouseDown={(e) => e.stopPropagation()}>
        <div className="modal-head">
          <h3>{title}</h3>
          <button className="ghost" onClick={onClose}>
            ✕
          </button>
        </div>
        {children}
      </div>
    </div>
  );
}
export function ErrorBox({ error }) {
  return error ? <div className="alert error">{error}</div> : null;
}
