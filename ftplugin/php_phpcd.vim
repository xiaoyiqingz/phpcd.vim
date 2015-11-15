let s:save_cpo = &cpo
set cpo&vim

silent! nnoremap <silent> <unique> <buffer> <C-]> :<C-u>call phpcd#JumpToDefinition('normal')<CR>
silent! nnoremap <silent> <unique> <buffer> <C-W><C-]> :<C-u>call phpcd#JumpToDefinition('split')<CR>
silent! nnoremap <silent> <unique> <buffer> <C-W><C-\> :<C-u>call phpcd#JumpToDefinition('vsplit')<CR>

if exists('g:phpcd_job_id')
	finish
endif

let root = phpcd#getComposerRoot()
if root == '/'
	finish
endif

let autoload_file = root . '/vendor/autoload.php'
let phpcd_path = expand('<sfile>:p:h:h') . '/php/phpcd_main.php'
let g:phpcd_job_id = jobstart([
			\ 'php',
			\ phpcd_path,
			\ $NVIM_LISTEN_ADDRESS,
			\ autoload_file])

let phpid_path = expand('<sfile>:p:h:h') . '/php/phpid_main.php'
let class_map_file = root . '/vendor/composer/autoload_classmap.php'
let g:phpid_job_id = jobstart(['php',
			\ phpid_path,
			\ $NVIM_LISTEN_ADDRESS,
			\ autoload_file,
			\ class_map_file,
			\ root])

call phpcd#initAutocmd()

let &cpo = s:save_cpo
unlet s:save_cpo

" vim: foldmethod=marker:noexpandtab:ts=2:sts=2:sw=2
