if (! window._babelPolyfill) {
  require('babel-polyfill');
}

import React from 'react';
import ReactDOM from 'react-dom';
import Admin from './containers/Admin';

document.addEventListener('DOMContentLoaded', function() {
  ReactDOM.render(<Admin />, document.getElementById('acp'));
});
