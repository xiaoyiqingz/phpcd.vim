let s:save_cpo = &cpo
set cpo&vim

if !exists('g:phpcd_php_cli_executable')
	let g:phpcd_php_cli_executable = 'php'
endif

silent! nnoremap <silent> <unique> <buffer> <C-]>
			\ :<C-u>call phpcd#JumpToDefinition('normal')<CR>
silent! nnoremap <silent> <unique> <buffer> <C-W><C-]>
			\ :<C-u>call phpcd#JumpToDefinition('split')<CR>
silent! nnoremap <silent> <unique> <buffer> <C-W><C-\>
			\ :<C-u>call phpcd#JumpToDefinition('vsplit')<CR>
silent! nnoremap <silent> <unique> <buffer> K
			\ :<C-u>call phpcd#JumpToDefinition('preview')<CR>
silent! nnoremap <silent> <unique> <buffer> <C-t>
			\ :<C-u>call phpcd#JumpBack()<CR>

command! -nargs=0 PHPID call phpcd#Index()

if has('nvim')
	let messenger = 'msgpack'
else
	let messenger = 'json'
end

let s:phpcd_path = expand('<sfile>:p:h:h') . '/php/main.php'
let s:autoload_path = g:phpcd_root.'/'.g:phpcd_autoload_path
let g:php_autoload_path = s:autoload_path
if exists('g:phpcd_channel_id')
	call rpc#stop(g:phpcd_channel_id)
endif
let g:phpcd_channel_id = rpc#start(g:phpcd_php_cli_executable,
			\ s:phpcd_path, g:phpcd_root, 'PHPCD', messenger, s:autoload_path)

if g:phpcd_root == '/'
	let &cpo = s:save_cpo
	unlet s:save_cpo
	finish
endif

if exists('g:phpid_channel_id')
	call rpc#stop(g:phpid_channel_id)
endif

let g:phpid_channel_id = rpc#start(g:phpcd_php_cli_executable,
			\ s:phpcd_path, g:phpcd_root, 'PHPID', messenger, s:autoload_path)

let &cpo = s:save_cpo
unlet s:save_cpo

" vim: foldmethod=marker:noexpandtab:ts=2:sts=2:sw=2
