# Theme baseline (W0)

The admin styling is unchanged from the template `main`. Do not restyle it; extend with the same tokens and components.

- Tokens: `packages/tokens/base.css` (shared), `packages/tokens/admin.css` (Tailwind 4 `@theme`, light and dark via CSS variables: canvas, surface, ink, primary scale, danger).
- Components in `web/admin/src/components`: `CrudPage`, `DataTable`, `FormField`, `ConfirmDialog`, `Toast`, `Icon`, `IconButton`, `ThemeToggle`, `Brand`, `Splash`, `ImagePanel`, `RichText`, `ToggleCell`, `BilingualField` (unused; the admin is English only).
- Layout: `web/admin/src/layout/AppShell.tsx`.
