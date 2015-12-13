let s:save_cpo = &cpo
set cpo&vim

let g:phpcd_need_update = 0

let root = phpcd#getComposerRoot()
if root == '/'
	finish
endif

let phpcd_path = expand('<sfile>:p:h:h') . '/php/phpcd_main.php'
let g:phpcd_channel_id = rpcstart('php', [phpcd_path, root])

let &cpo = s:save_cpo
unlet s:save_cpo

" vim: foldmethod=marker:noexpandtab:ts=2:sts=2:sw=2
