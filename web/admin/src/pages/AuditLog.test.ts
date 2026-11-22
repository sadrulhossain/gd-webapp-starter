import { describe, expect, it } from 'vitest'
import { auditQuery, diffRows } from './AuditLog'

describe('audit log helpers', () => {
  it('sends only the filters that were filled in, with the page', () => {
    expect(auditQuery({ event: 'updated', auditable_type: '', from: '', to: '2026-11-01' }, 2)).toEqual({ page: 2, event: 'updated', to: '2026-11-01' })
  })

  it('lines up before and after for every attribute, keeping redacted values as they are', () => {
    expect(diffRows({ old_values: { name: 'Jo' }, new_values: { name: 'Joe', password: '[redacted]' } })).toEqual([
      { name: 'name', before: 'Jo', after: 'Joe' },
      { name: 'password', before: '', after: '[redacted]' },
    ])
    expect(diffRows({ old_values: null, new_values: null })).toEqual([])
  })
})
