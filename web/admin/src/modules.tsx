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
    columns: [
      { key: 'name', header: 'Name' },
      { key: 'email', header: 'Email' },
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
    // Seeded tree, edit-only. Rows arrive depth-first; drag reorders within the same parent.
    key: 'menus',
    path: '/menus',
    title: 'Menus',
    noun: 'Menu entry',
    endpoint: '/v1/menus',
    canCreate: false,
    canDelete: false,
    reorder: { endpoint: '/v1/menus/reorder', group: (r) => r.parent_id ?? null },
    rowLabel: (r) => String(r.title),
    columns: [
      { key: 'title', header: 'Title', render: (r) => <span style={{ paddingLeft: `${Number(r.depth ?? 0) * 1.5}rem` }}>{String(r.title)}</span> },
      { key: 'path', header: 'Path' },
      { key: 'permission_name', header: 'Permission' },
    ],
    fields: [
      { name: 'title', label: 'Title', required: true },
      { name: 'icon', label: 'Icon', type: 'icon', hint: 'Shown beside the menu entry in the sidebar.' },
    ],
  },
]
