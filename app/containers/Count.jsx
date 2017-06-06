import React, {Component} from 'react';
import PropTypes from 'prop-types';

export default class Count extends Component {

    constructor() {
        super();
    }

    render() {
        return (
            <header className="acp-results-count">
                <span className="acp-count-info">
                    <span className="amount">{this.props.count}</span> Result{this.props.count === 1 ? '' : 's'} {this.props.search !== '' ? <a className="clear" title="&#8984; + &#9003;" onClick={this.props.clearInput}>Clear</a> : '' }
                </span>
            </header>
        );
    }

    static propTypes = {
        count: PropTypes.number.isRequired,
        search: PropTypes.string.isRequired,
        clearInput: PropTypes.func.isRequired,
    }
}