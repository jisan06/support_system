// Shim: re-exports the globally loaded Bootstrap (from bootstrap.bundle.min.js)
// This prevents laravel-datatables-vite from bundling a second copy of Bootstrap
export default window.bootstrap;
export const Modal = window.bootstrap?.Modal;
export const Tooltip = window.bootstrap?.Tooltip;
export const Dropdown = window.bootstrap?.Dropdown;
export const Collapse = window.bootstrap?.Collapse;
export const Tab = window.bootstrap?.Tab;
export const Alert = window.bootstrap?.Alert;
export const Popover = window.bootstrap?.Popover;
