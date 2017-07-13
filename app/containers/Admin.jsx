import React, {Component} from 'react';
import fetch from 'isomorphic-fetch';
import Mousetrap from 'mousetrap';
import Modal from './Modal';

const initialState = {
    modalOpen: false,
    helpOpen: false,
    search: '',
    results: [],
    count: 0,
    postTypes: acp_object.helpData.postTypes,
    taxonomies: acp_object.helpData.taxonomies,
};

export default class Admin extends Component {

    constructor(props) {
        super(props);

        this.state = initialState;
    }

    componentDidMount() {
        Mousetrap.bind('shift shift', () => this.toggleModal());
        Mousetrap.bind('esc', () => this.closeModal());

        Mousetrap.prototype.stopCallback = function (e, element) {
            return element.tagName === 'INPUT' && e.key !== 'Escape';
        }
    }

    componentWillUnmount() {
        Mousetrap.unbind('shift shift', () => this.revealModal);
        Mousetrap.unbind('esc', () => this.revealModal);
    }

    reset = () => {
        this.setState(initialState);
    };

    toggleModal = () => {
        let modalOpen = this.state.modalOpen;
        let newState = initialState;
        newState.modalOpen = !modalOpen;

        this.setState(newState);
    };

    revealModal = (revealed) => {

        this.setState({
            modalOpen: revealed
        })
    };

    closeModal = () => {
        this.revealModal(false);
    };

    getSearchResults = () => {

        if ('' === this.state.search) {

            // Reset the results if the search has been cleared out.
            this.setState({
                results: []
            });

            return;
        }

        fetch(`${this.searchUrl()}`, {
            credentials: 'same-origin',
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': acp_object.api_nonce,
            },
        })
            .then(response => response.json())
            .then(
                (json) => this.setState({
                    results: json.results,
                    count: json.count,
                }),
                (err) => console.log('error', err)
            );
    };

    searchUrl = () => {
        let url = acp_object.api_search_url;
        url += 'false' === acp_object.api_pretty_permalink ? '&' : '?';
        url += 'command=' + this.state.search;

        return url;
    };

    updateInput = (e) => {

        const searchTerm = e.target.value;

        if ('' === searchTerm) {
            this.clearInput();
        }

        this.setState({
            search: searchTerm,
        }, this.getSearchResults);
    };

    clearInput = () => {

        this.setState({
            search: '',
            results: [],
            count: 0,
        });
    };

    toggleHelp = () => {

        this.setState({
            helpOpen: !this.state.helpOpen,
        });
    };

    render() {

        return (
            <div>
                {
                    this.state.modalOpen
                        ? <Modal
                        closeModal={this.closeModal}
                        search={this.state.search}
                        results={this.state.results}
                        count={this.state.count}
                        updateInput={this.updateInput}
                        clearInput={this.clearInput}
                        toggleHelp={this.toggleHelp}
                        helpOpen={this.state.helpOpen}
                        postTypes={this.state.postTypes}
                        taxonomies={this.state.taxonomies}
                    />
                        : null
                }
            </div>
        )
    }
}
