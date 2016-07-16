function! rpc#request(...) " {{{
	if has('nvim')
		return call('rpcrequest', a:000)
	else
		" todo
	end
endfunction " }}}

function! rpc#notify(...) " {{{
	if has('nvim')
		return call('rpcnotify', a:000)
	else
		" todo
	end
endfunction " }}}

function! rpc#start(...) " {{{
	if has('nvim')
		return call('rpcstart', a:000)
	else
		" todo
	end
endfunction " }}}

function! rpc#stop(...) " {{{
	if has('nvim')
		return call('rpcstop', a:000)
	else
		" todo
	end
endfunction " }}}

" vim: foldmethod=marker:noexpandtab:ts=2:sts=2:sw=2
