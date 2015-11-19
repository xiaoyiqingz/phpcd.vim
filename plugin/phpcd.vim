let s:save_cpo = &cpo
set cpo&vim

let g:phpcd_channel_id = -1
let g:phpid_channel_id = -1
let g:phpcd_need_update = 0

let &cpo = s:save_cpo
unlet s:save_cpo

" vim: foldmethod=marker:noexpandtab:ts=2:sts=2:sw=2
