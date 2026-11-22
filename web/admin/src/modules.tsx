import { api } from './api'
import type { CrudConfig } from './components/CrudPage'

/** "faqs.view" -> row "faqs", column "view". */
export function permissionOption(name: string) {
  const [group = name, ...action] = name.split('.')

  return { value: name, label: action.join('.') || name, group }
}

/**
 * Admin modules are data. Each entry becomes a route (`path`, gated by `${key}.view`) and a CrudPage.
 * Sidebar entries come from the API (/me/menus), so a module also needs its menu row seeded.
 */
export const modules: CrudConfig[] = [
  {
    key: 'users',
    path: '/users',
    title: 'Users',
    noun: 'User',
    endpoint: '/v1/users',
    rowLabel: (r) => String(r.email),
    // The API refuses to edit or delete a superadmin; the buttons are simply not offered.
    rowCanEdit: (r) => !r.is_superadmin,
    rowCanDelete: (r) => !r.is_superadmin,
    rowActions: [
      {
        label: 'Suspend',
        icon: 'x',
        permission: 'users.update',
        show: (r) => !r.is_superadmin && r.status === 'active',
        confirm: (r) => `Suspend ${String(r.email)}? They are signed out everywhere and cannot sign in until reactivated.`,
        run: (r) => api.request('PATCH', `/v1/users/${r.id}/status`, { body: { status: 'suspended' } }),
        done: 'User suspended',
      },
      {
        label: 'Reactivate',
        icon: 'check',
        permission: 'users.update',
        show: (r) => r.status === 'suspended',
        run: (r) => api.request('PATCH', `/v1/users/${r.id}/status`, { body: { status: 'active' } }),
        done: 'User reactivated',
      },
      {
        label: 'Sign out everywhere',
        icon: 'log-out',
        permission: 'users.update',
        confirm: (r) => `Sign ${String(r.email)} out of every device?`,
        run: (r) => api.request('DELETE', `/v1/users/${r.id}/sessions`),
        done: 'Signed out everywhere',
      },
    ],
    columns: [
      { key: 'name', header: 'Name' },
      { key: 'email', header: 'Email' },
      { key: 'status', header: 'Status', render: (r) => (r.is_superadmin ? 'superadmin' : String(r.status)) },
      { key: 'roles', header: 'Roles', render: (r) => (Array.isArray(r.roles) ? r.roles.join(', ') : '') },
    ],
    fields: [
      { name: 'name', label: 'Name', required: true },
      { name: 'email', label: 'Email', required: true },
      { name: 'password', label: 'Password', type: 'password', hint: 'At least 12 characters. When editing, leave blank to keep the current one.' },
      { name: 'roles', label: 'Roles', type: 'checkgroup', optionsFrom: { endpoint: '/v1/roles', map: (role: { name: string }) => ({ value: role.name, label: role.name }) } },
    ],
  },
  {
    // The matrix: one row per module, one column per action, built from /v1/permissions ("faqs.view" -> row faqs, column view).
    key: 'roles',
    path: '/roles',
    title: 'Roles',
    noun: 'Role',
    endpoint: '/v1/roles',
    rowLabel: (r) => String(r.name),
    columns: [
      { key: 'name', header: 'Name' },
      { key: 'permissions', header: 'Permissions', render: (r) => (Array.isArray(r.permissions) ? r.permissions.length : 0) },
    ],
    fields: [
      { name: 'name', label: 'Name', required: true, hint: 'Lowercase letters, numbers, dashes and underscores.' },
      { name: 'permissions', label: 'Permissions', type: 'checkgroup', optionsFrom: { endpoint: '/v1/permissions', map: permissionOption } },
    ],
  },
  {
    // The tree, depth-first; drag reorders within the same parent. Seeded rows keep their path and permission and cannot be deleted;
    // custom rows (added here) can change both and be deleted.
    key: 'menus',
    path: '/menus',
    title: 'Menus',
    noun: 'Menu entry',
    endpoint: '/v1/menus',
    rowCanDelete: (r) => r.custom === true,
    reorder: { endpoint: '/v1/menus/reorder', group: (r) => r.parent_id ?? null },
    rowLabel: (r) => String(r.title),
    columns: [
      { key: 'title', header: 'Title', render: (r) => <span style={{ paddingLeft: `${Number(r.depth ?? 0) * 1.5}rem` }}>{String(r.title)}</span> },
      { key: 'path', header: 'Path' },
      { key: 'permission_name', header: 'Permission' },
      { key: 'active', header: 'Visible', render: (r) => (r.active ? 'Yes' : 'Hidden') },
    ],
    fields: [
      {
        name: 'parent_id',
        label: 'Group',
        type: 'select',
        hint: 'Leave empty for a top-level entry.',
        when: (_v, row) => row === null,
        optionsFrom: { endpoint: '/v1/menus', filter: (m: { permission_name: string | null }) => m.permission_name === null, map: (m: { id: number; title: string }) => ({ value: m.id, label: m.title }) },
      },
      { name: 'title', label: 'Title', required: true },
      { name: 'icon', label: 'Icon', type: 'icon', hint: 'Shown beside the menu entry in the sidebar.' },
      { name: 'active', label: 'Visible in the sidebar', type: 'checkbox', initial: true, hint: 'Hiding an entry hides its children too; the pages stay protected by permissions.' },
      {
        name: 'permission_name',
        label: 'Permission',
        type: 'select',
        hint: 'Who sees the link. Empty makes a group. You can only pick permissions you hold.',
        when: (_v, row) => row === null || row.custom === true,
        optionsFrom: { endpoint: '/v1/permissions', map: (name: string) => ({ value: name, label: name }) },
      },
      { name: 'path', label: 'Path', hint: 'For example /reports. Needed with a permission.', when: (_v, row) => row === null || row.custom === true },
    ],
  },
]
