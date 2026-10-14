import { api } from './http';
import { toast, showErrors, clearErrors, debounce, renderPagination, el, emptyRow } from './ui';
import { initRequestForm, bindDigitBoxes, toHijri } from './request-form';

window.App = { api, toast, showErrors, clearErrors, debounce, renderPagination, el, emptyRow, initRequestForm, bindDigitBoxes, toHijri };
