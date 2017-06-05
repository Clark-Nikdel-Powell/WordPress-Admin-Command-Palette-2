import React, {Component} from 'react';
import fetch from 'isomorphic-fetch';
import Mousetrap from 'mousetrap';
import Modal from './Modal';

export default class Admin extends Component {

    constructor(props) {
        super(props);

        this.state = {
            modalOpen: false,
            search: '',
            results: [],
            count: 0,
        };
    }

    componentDidMount() {
        Mousetrap.bind('shift shift', () => this.revealModal(true));
        Mousetrap.bind('esc', () => this.revealModal(false));
    }

    componentWillUnmount() {
        Mousetrap.unbind('shift shift', () => this.revealModal);
        Mousetrap.unbind('esc', () => this.revealModal);
    }

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

        fetch(`${acp_object.api_url}/?search=${this.state.search}`, {
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

    render() {

        return (
            <div>
                {
                    this.state.modalOpen
                        ? <Modal closeModal={this.closeModal} search={this.state.search} results={this.state.results} count={this.state.count}
                                 updateInput={this.updateInput} clearInput={this.clearInput}/>
                        : null
                }
            </div>
        )
    }
}
