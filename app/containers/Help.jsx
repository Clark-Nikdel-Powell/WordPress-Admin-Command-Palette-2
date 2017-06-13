import React, {Component} from 'react';
import PropTypes from 'prop-types';

export default class Help extends Component {

    constructor() {
        super();
    }

    render() {
        return (
            <div className="acp-help">
                <a className="acp-help-trigger" onClick={this.props.toggleHelp}>Help {this.props.helpOpen === true ? '-' : '+'}</a>
                {
                    this.props.helpOpen
                        ? <ul className="acp-help-items">
                        <li>
                            <span className="acp-help-text">Use ":" to search by post type, e.g. ":page what"</span>
                            <span
                                className="acp-help-description">Available post types: {this.props.postTypes.join(', ')}</span>
                        </li>
                        <li><span className="acp-help-text">Use "-" to do a negative search.</span></li>
                        <li>
                            <span className="acp-help-text">Use "/" to send a command. Available commands are:</span>
                            <ul className="acp-help-description">
                                <li>"/ap": Activate an inactive plugin.</li>
                                <li>"/dp": Deactivate an active plugin.</li>
                            </ul>
                        </li>
                        <li><span className="acp-help-text">Press <kbd>esc</kbd> or click on the overlay to close the modal.</span>
                        </li>
                    </ul>
                        : null
                }
            </div>
        )
    }

    static propTypes = {
        toggleHelp: PropTypes.func.isRequired,
        helpOpen: PropTypes.bool.isRequired,
        postTypes: PropTypes.array.isRequired,
    }
}