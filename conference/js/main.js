/*
    copyright (c) 2015 MEGA-NET.RU 
    Authors: Nikolaev Dmitry <dn@mega-net.ru>, Panfilov Alexey <ap@mega-net.ru>
*/


if( typeof Date.defineFormat === 'function' ){
    Date.defineFormat( 'time', '%d.%m.%Y %T' );
}

function StasisStart( obj ){
    console.log('StasisStart');
    if (is_null(obj.application)){
	console.log('Application is unknown');
    }else{
	$('wsDebug').set('html','Got connection to app '+ obj.application+'<BR>');
	$('wsDebug').appendHTML('From "'+obj.channel.caller.name+'" &lt;'+ obj.channel.caller.number +'&gt;');
	
	var callID=obj.channel.id.replace( /\./, '_' );
	if (is_null($('u'+callID))){
		var tmp = new Element( 'div', {
		    class: 'connectedUser',
	    	    id: 'u'+callID,
		    html: '<div class="connectedUserHeader">Connected since '+ new Date( ).format('time') +'</div><b>"'+obj.channel.caller.name+'" &lt;'+ obj.channel.caller.number +'&gt;</b>'
		} );

		var btn = new Element( 'div', {
		    styles:{
			'display':'inline'
		    }
		} ).adopt( 
		    new Element( 'ul',{} ).adopt( 
			new Element( 'li', { 
				id: 'mute_'+callID,
				class: '' ,
				html: 'MUTE',
				events: {
				    click: function( ){
					console.log('MUTE '+callID);
					$('unmute_'+callID).removeClass('on');
					$('mute_'+callID).addClass('on');
					socket.send(JSON.stringify({mute:true,channel:obj.channel.id}));
				    }
				}
			} ) 
		    ).adopt( 
			new Element( 'li', { 
				id: 'unmute_'+callID,
				class: 'on' , 
				html: 'UNMUTE',
				events: {
				    click: function( ){
					console.log('UNMUTE '+callID);
					$('mute_'+callID).removeClass('on');
					$('unmute_'+callID).addClass('on');
					socket.send(JSON.stringify({mute:false,channel:obj.channel.id}));
				    }
				}
			} ) 
		    )
		);
		var btn2 = new Element ( 'button', {
			id: 'kick_'+callID,
			class: 'btn btn-default',
			html: 'KICK',
			events: {
			    click: function( ){
				if (confirm('Kick '+obj.channel.caller.name+'?')) {
				    console.log('Kick '+callID);
				    socket.send(JSON.stringify({kick:true,channel:obj.channel.id}));
				}
			    }
			}
		});
		btn.adopt( btn2 );
		tmp.adopt( btn );
		$( 'connectedUsers' ).adopt( tmp );
	}else{
	    console.log('u'+callID+' exists');
	}
    }
}

function StasisEnd( obj ){
    console.log('StasisEnd');
    if (is_null(obj.application)){
	console.log('Application is unknown');
    }else{
	$('wsDebug').set('html','Got disconnection from app '+ obj.application+'<BR>');
	$('wsDebug').appendHTML('<font color=red>Disconnected "'+obj.channel.caller.name+'" &lt;'+ obj.channel.caller.number +'&gt;</font>');

	callID=obj.channel.id.replace( /\./, '_' );
	if (!is_null( $('u'+callID) )){
	    $('u'+callID).destroy( );
	}
    }
}

function debug( text ){
    if( typeof Date.defineFormat === 'function' ){
	Date.defineFormat( 'logtime', '[%d.%m.%Y %T.%L]: ' );
    }
    console.log( new Date( ).format( 'logtime' ) );
    Object.each( arguments, function( v, k ){
	console.log( ' ', v );
    } );
}

function is_null( obj ){
    if ( obj === undefined ){
	return 1;
    }else if ( obj === null ){
	return 1;
    }else if ( obj == 0 ){
	return 1;
    }else if ( obj == '' ){
	return 1;
    }
    return 0;
}
