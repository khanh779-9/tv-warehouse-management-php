import React, { useEffect, useState } from "react";
import { api } from "../api";
import { ErrorBox } from "../components";
export default function AuditLogs() {
  const [rows, setRows] = useState([]),
    [error, setError] = useState("");
  useEffect(() => {
    api("/audit-logs?per_page=100")
      .then((d) => setRows(d.data))
      .catch((e) => setError(e.message));
  }, []);
  return (
    <>
      <ErrorBox error={error} />
      <div className="panel table-wrap">
        <div className="panel-title">
          <h3>Business audit trail</h3>
          <span className="muted">Who changed what and when</span>
        </div>
        <table>
          <thead>
            <tr>
              <th>Time</th>
              <th>User</th>
              <th>Action</th>
              <th>Entity</th>
              <th>ID</th>
              <th>IP</th>
            </tr>
          </thead>
          <tbody>
            {rows.map((x) => (
              <tr key={x.id}>
                <td>{new Date(x.created_at).toLocaleString()}</td>
                <td>{x.user?.name || "System"}</td>
                <td>
                  <b>{x.action}</b>
                </td>
                <td>{x.entity_type}</td>
                <td>{x.entity_id || "—"}</td>
                <td>{x.ip_address || "—"}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </>
  );
}
