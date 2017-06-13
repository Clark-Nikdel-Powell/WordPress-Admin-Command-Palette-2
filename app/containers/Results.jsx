import React, {Component} from 'react';
import PropTypes from 'prop-types';

export default class Results extends Component {

    constructor() {
        super();
        this.renderResults = this.renderResults.bind(this);
    }

    renderResults(key) {
        const result = this.props.results[key];

        return (
            <li key={key}>
                <a href={result.url}>
                    <div>{result.title}</div>
                    <small className="acp-subtitle"><strong>{result.object_type}</strong>{result.subtitle}</small>
                </a>
            </li>
        )
    }

    render() {
        const resultIds = Object.keys(this.props.results);

        return (
            <div className="acp-results-container">
                <div className="acp-results">
                    <ul className="acp-list">
                        {resultIds.map(this.renderResults)}
                    </ul>
                </div>
            </div>
        )
    }

    static propTypes = {
        results: PropTypes.array.isRequired,
    }
}