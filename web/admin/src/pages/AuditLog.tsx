import { keepPreviousData, useQuery } from '@tanstack/react-query'
import { useState } from 'react'
import { api } from '../api'
import { Modal } from '../components/ConfirmDialog'
import { DataTable } from '../components/DataTable'
import { IconButton } from '../components/IconButton'
import type { Listing } from '../types'

export interface AuditEntry {
  id: number
  event: string
  user: string | null
  user_id: number | null
  auditable_type: string | null
  auditable_id: string | null
  auditable: string | null
  old_values: Record<string, unknown> | null
  new_values: Record<string, unknown> | null
  ip: string | null
  created_at: string
}

interface Filters {
  event: string
  auditable_type: string
  from: string
  to: string
}

const EMPTY: Filters = { event: '', auditable_type: '', from: '', to: '' }

/** Only the filters the user filled in, as the query the API expects. */
export function auditQuery(filters: Filters, page: number): Record<string, string | number> {
  return { page, ...Object.fromEntries(Object.entries(filters).filter(([, v]) => v !== '')) }
}

/** Every attribute that appears in the old or new values, with both sides, for the detail dialog. */
export function diffRows(entry: Pick<AuditEntry, 'old_values' | 'new_values'>): { name: string; before: string; after: string }[] {
  const show = (v: unknown) => (v === undefined ? '' : typeof v === 'string' ? v : JSON.stringify(v))
  const names = [...new Set([...Object.keys(entry.old_values ?? {}), ...Object.keys(entry.new_values ?? {})])]

  return names.map((name) => ({ name, before: show(entry.old_values?.[name]), after: show(entry.new_values?.[name]) }))
}

const when = (iso: string) => new Date(iso).toLocaleString()

/** Read-only, newest first. The log is append-only, so there is nothing to edit or delete here. */
export function AuditLogPage() {
  const [filters, setFilters] = useState<Filters>(EMPTY)
  const [applied, setApplied] = useState<Filters>(EMPTY)
  const [page, setPage] = useState(1)
  const [open, setOpen] = useState<AuditEntry | null>(null)

  const list = useQuery({
    queryKey: ['audit-logs', applied, page],
    placeholderData: keepPreviousData,
    queryFn: async () => (await api.request('GET', '/v1/audit-logs', { query: auditQuery(applied, page) })) as Listing<AuditEntry>,
  })
  const meta = list.data?.meta
  const set = (name: keyof Filters) => (e: { target: { value: string } }) => setFilters((f) => ({ ...f, [name]: e.target.value }))

  return (
    <section className="space-y-4">
      <h1 className="text-2xl font-semibold">Audit log</h1>

      <form
        className="card flex flex-wrap items-end gap-3 p-3"
        onSubmit={(e) => {
          e.preventDefault()
          setPage(1)
          setApplied(filters)
        }}
      >
        <label className="space-y-1 text-sm text-ink-muted">
          Event
          <input className="field" value={filters.event} onChange={set('event')} placeholder="created, updated..." />
        </label>
        <label className="space-y-1 text-sm text-ink-muted">
          Type
          <select className="field" value={filters.auditable_type} onChange={set('auditable_type')}>
            <option value="">All</option>
            {['User', 'Role', 'Menu', 'Setting'].map((t) => (
              <option key={t}>{t}</option>
            ))}
          </select>
        </label>
        <label className="space-y-1 text-sm text-ink-muted">
          From
          <input type="date" className="field" value={filters.from} onChange={set('from')} />
        </label>
        <label className="space-y-1 text-sm text-ink-muted">
          To
          <input type="date" className="field" value={filters.to} onChange={set('to')} />
        </label>
        <IconButton showText type="submit" variant="primary" icon="check" label="Apply" />
        <IconButton
          showText
          variant="secondary"
          icon="x"
          label="Clear"
          onClick={() => {
            setFilters(EMPTY)
            setApplied(EMPTY)
            setPage(1)
          }}
        />
      </form>

      {list.isError ? (
        <p role="alert" className="text-danger">
          Could not load the audit log.
        </p>
      ) : (
        <DataTable<AuditEntry>
          columns={[
            { key: 'created_at', header: 'When', render: (r) => when(r.created_at) },
            { key: 'user', header: 'Who', render: (r) => r.user ?? 'system' },
            { key: 'event', header: 'Event' },
            { key: 'auditable', header: 'What', render: (r) => [r.auditable_type, r.auditable].filter(Boolean).join(': ') },
            { key: 'ip', header: 'IP' },
          ]}
          rows={list.data?.data ?? []}
          rowKey={(r) => r.id}
          loading={list.isPending}
          empty="No entries match."
          actions={(row) => <IconButton variant="row" icon="eye" label="Details" onClick={() => setOpen(row)} />}
        />
      )}

      {meta && meta.last_page > 1 && (
        <nav className="flex items-center justify-end gap-3" aria-label="Pagination">
          <IconButton variant="secondary" icon="chevron-left" label="Previous" disabled={page <= 1} onClick={() => setPage(page - 1)} />
          <span className="text-ink-muted">
            {meta.current_page} / {meta.last_page}
          </span>
          <IconButton variant="secondary" icon="chevron-right" label="Next" disabled={page >= meta.last_page} onClick={() => setPage(page + 1)} />
        </nav>
      )}

      <Modal size="lg" open={open !== null} title={open ? `${open.event}: ${[open.auditable_type, open.auditable].filter(Boolean).join(' ')}` : ''} onClose={() => setOpen(null)}>
        {open && (
          <div className="space-y-3">
            <p className="text-sm text-ink-muted">
              {when(open.created_at)} by {open.user ?? 'system'}
              {open.ip ? ` from ${open.ip}` : ''}
            </p>
            {diffRows(open).length === 0 ? (
              <p className="text-ink-muted">No values were recorded for this event.</p>
            ) : (
              <div className="card overflow-x-auto">
                <table className="w-full text-left text-sm">
                  <thead className="border-b border-line bg-surface-muted text-ink-muted">
                    <tr>
                      <th scope="col" className="px-3 py-2">Attribute</th>
                      <th scope="col" className="px-3 py-2">Before</th>
                      <th scope="col" className="px-3 py-2">After</th>
                    </tr>
                  </thead>
                  <tbody>
                    {diffRows(open).map((r) => (
                      <tr key={r.name} className="border-b border-line last:border-0">
                        <th scope="row" className="px-3 py-2 font-medium">{r.name}</th>
                        <td className="px-3 py-2 break-all">{r.before}</td>
                        <td className="px-3 py-2 break-all">{r.after}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </div>
        )}
      </Modal>
    </section>
  )
}
