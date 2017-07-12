import React, {Component} from 'react';
import PropTypes from 'prop-types';
import Results from './Results';
import Count from './Count';
import Help from './Help';

export default class Modal extends Component {

    constructor() {
        super();
    }

    componentDidMount(){
        this.searchInput.focus();
    }

    render() {
        return (
            <div className="wrap">
                <div className="acp acp-overlay" onClick={this.props.closeModal}> </div>
                <div className="acp acp-modal">
                    <div className="search-container">
                        <input
                            type="search"
                            placeholder="Start typing..."
                            ref={(input) => {
                                this.searchInput = input;
                            }}
                            value={this.props.search}
                            onChange={this.props.updateInput}
                        />
                    </div>
                    <Count count={this.props.count} search={this.props.search} clearInput={this.props.clearInput} />
                    <Help
                        toggleHelp={this.props.toggleHelp}
                        helpOpen={this.props.helpOpen}
                        postTypes={this.props.postTypes}
                        taxonomies={this.props.taxonomies}
                    />
                    <Results
                        results={this.props.results}
                    />
                </div>
            </div>
        );
    }

    static propTypes = {
        closeModal: PropTypes.func.isRequired,
        search: PropTypes.string.isRequired,
        updateInput: PropTypes.func.isRequired,
        count: PropTypes.number.isRequired,
        clearInput: PropTypes.func.isRequired,
        results: PropTypes.array.isRequired,
        toggleHelp: PropTypes.func.isRequired,
        helpOpen: PropTypes.bool.isRequired,
        postTypes: PropTypes.array.isRequired,
        taxonomies: PropTypes.array.isRequired,
    }
}