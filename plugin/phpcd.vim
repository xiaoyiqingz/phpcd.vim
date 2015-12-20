let s:save_cpo = &cpo
set cpo&vim

let g:phpcd_need_update = 0

function! GetComposerRoot() " {{{
	let root = getcwd()
	while root != "/"
		if (filereadable(root . "/vendor/autoload.php"))
			break
		endif
		let root = fnamemodify(root, ":h")
	endwhile
	return root
endfunction " }}}

let root = GetComposerRoot()

if root == '/'
	let &cpo = s:save_cpo
	unlet s:save_cpo
	finish
endif

let phpcd_path = expand('<sfile>:p:h:h') . '/php/phpcd_main.php'
let g:phpcd_channel_id = rpcstart('php', [phpcd_path, root])

let phpid_path = expand('<sfile>:p:h:h') . '/php/phpid_main.php'
let g:phpid_channel_id = rpcstart('php', [phpid_path, root])

autocmd BufLeave,VimLeave *.php if g:phpcd_need_update > 0 | call phpcd#UpdateIndex() | endif
autocmd BufWritePost *.php let g:phpcd_need_update = 1

let &cpo = s:save_cpo
unlet s:save_cpo

" vim: foldmethod=marker:noexpandtab:ts=2:sts=2:sw=2
