/**
 * Reactions Module
 * Usage:
 *
 * // Initialize reactions for all topics on page load
 * $(document).ready(function() {
 *   $('.reactions-topic').each(function() {
 *     ReactionsModule.init(this, {
 *       topicType: $(this).data('topic-type'),
 *       topicId: $(this).data('topic-id'),
 *       initialReactions: {
 *         like: 5,
 *         celebrate: 3,
 *         support: 2
 *       },
 *       currentReaction: 'like'
 *     });
 *   });
 * });
 */

const ReactionsModule = (function() {
    // Private variables
    const emojiMap = {
        defaultlike: '1f44d-gray', // Used for unselected state
        like: '1f44d',
        celebrate: '1f44f',
        support: '1f397',
        love: '1f60d',
        insightful: '1f4a1',
	gratitude: '1f64f',
        //curious: '1f914',
    };

    const labelMap = {
        defaultlike: 'Like', // Used for unselected state
        like: 'Like',
        celebrate: 'Celebrate',
        support: 'Support',
        love: 'Love',
        insightful: 'Insightful',
	gratitude: 'Gratitude',
        //curious: 'Curious'
    };

    // Default API service - override with your implementation
    let apiService = {
        getReactions: function(topicType, topicId) {
            console.log(`[ReactionsModule] Fetching reactions for ${topicType} ${topicId}`);
            return $.Deferred().resolve({
                counts: {},
                currentReaction: null
            }).promise();
        },

        setReaction: function(topicType, topicId, likeUnlikeMethod, reaction) {
            return likeUnlikeTopicCommon(topicId, likeUnlikeMethod, reaction);
        },

        removeReaction: function(topicType, topicId, likeUnlikeMethod, reaction) {
            return likeUnlikeTopicCommon(topicId, likeUnlikeMethod, reaction);
        }
    };

    // Private methods
    function createReactionHTML(floatToolbarDirection) {
        return `
        <div class="reaction-container d-flex" role="group" aria-label="Topic reactions" style="width: 100%">
            <button class="reaction-button ml-1" aria-haspopup="true" aria-expanded="false" aria-label="React to this topic">
                <span class="reaction-icon" aria-hidden="true">
                    <img src="/1/image/twemoji/14.0.2/assets/svg/${emojiMap.like}.svg" width="24" height="24" alt="">
                </span>
                <span class="reaction-label">Like</span>
            </button>
            <div class="reaction-options ${floatToolbarDirection}" role="menu" aria-hidden="true">
                <div class="reaction-option" data-reaction="like" data-emoji="${emojiMap.like}" role="menuitem" tabindex="0" aria-label="Like">
                    <img src="/1/image/twemoji/14.0.2/assets/svg/${emojiMap.like}.svg" alt="" aria-hidden="true">
                    <span class="tooltip">Like</span>
                </div>
                <div class="reaction-option" data-reaction="celebrate" data-emoji="${emojiMap.celebrate}" role="menuitem" tabindex="0" aria-label="Celebrate">
                    <img src="/1/image/twemoji/14.0.2/assets/svg/${emojiMap.celebrate}.svg" alt="" aria-hidden="true">
                    <span class="tooltip">Celebrate</span>
                </div>
<div class="reaction-option" data-reaction="support" data-emoji="${emojiMap.support}" role="menuitem" tabindex="0" aria-label="Support">
                    <img src="/1/image/twemoji/14.0.2/assets/svg/${emojiMap.support}.svg" alt="" aria-hidden="true">
                    <span class="tooltip">Support</span>
                </div>
                <div class="reaction-option" data-reaction="love" data-emoji="${emojiMap.love}" role="menuitem" tabindex="0" aria-label="Love">
                    <img src="/1/image/twemoji/14.0.2/assets/svg/${emojiMap.love}.svg" alt="" aria-hidden="true">
                    <span class="tooltip">Love</span>
                </div>
                <div class="reaction-option" data-reaction="insightful" data-emoji="${emojiMap.insightful}" role="menuitem" tabindex="0" aria-label="Insightful">
                    <img src="/1/image/twemoji/14.0.2/assets/svg/${emojiMap.insightful}.svg" alt="" aria-hidden="true">
                    <span class="tooltip">Insightful</span>
                </div>
                <div class="reaction-option" data-reaction="gratitude" data-emoji="${emojiMap.gratitude}" role="menuitem" tabindex="0" aria-label="Gratitude">
                    <img src="/1/image/twemoji/14.0.2/assets/svg/${emojiMap.gratitude}.svg" alt="" aria-hidden="true">
                    <span class="tooltip">Gratitude</span>
                </div>
            </div>

<div class="reaction-counter ml-auto mr-1" style="display: none;" tabindex="0" aria-label="View reaction details">
                <div class="reaction-counter-icons" aria-hidden="true"></div>
                <div class="reaction-counter-count">0</div>
            </div>
        </div>
        `;
    }

    function bindEvents(container, topicType, topicId, likeUnlikeMethod, state) {
        const $container = $(container);
        const $reactionButton = $container.find('.reaction-button');
        const $reactionOptions = $container.find('.reaction-options');
        const $reactionIcon = $container.find('.reaction-icon');
        const $reactionLabel = $container.find('.reaction-label');
        const $reactionCounter = $container.find('.reaction-counter');
        const $reactionCounterIcons = $container.find('.reaction-counter-icons');
        const $reactionCounterCount = $container.find('.reaction-counter-count');
        const $reactionSummary = $container.find('.reaction-summary');

        let timeoutId = null;

        // Show reaction options on hover
        $reactionButton.on('mouseenter', function() {
            clearTimeout(timeoutId);
            $reactionOptions.show();
        });

        // Hide reaction options when mouse leaves
        $reactionButton.on('mouseleave', function() {
            timeoutId = setTimeout(() => {
                if (!$reactionOptions.is(':hover')) {
                    $reactionOptions.hide();
                }
            }, 200);
        });

        // Handle mouse over/out for options container
        $reactionOptions.on('mouseenter', function() {
            clearTimeout(timeoutId);
        }).on('mouseleave', function() {
            $(this).hide();
        });

        // Toggle reaction summary
        $reactionCounter.on('click', function(e) {
            getLikersList(topicId, likeUnlikeMethod);
            e.stopPropagation();
            $reactionSummary.toggle();
        });

        // Handle reaction selection
        $container.find('.reaction-option').on('click', function() {
            const $this = $(this);
            const reaction = $this.data('reaction');

            // Toggle reaction if clicking the same one
            if (state.currentReaction === reaction) {
                removeReaction();
            } else {
                setReaction(reaction);
            }

            // Hide options
            $reactionOptions.hide();
        });

	// Add keyboard navigation for reaction options
        $container.find('.reaction-option').on('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                $(this).trigger('click');
            }
        });

        // Add keyboard navigation for reaction counter
        $reactionCounter.on('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                $(this).trigger('click');
            }
        });

        // Update aria-expanded state
        $reactionButton.on('focus', function() {
            $(this).attr('aria-expanded', $reactionOptions.is(':visible'));
        });

        // Close reactions when clicking outside
        $(document).on('click.reaction-' + topicType + '-' + topicId, function(e) {
            if (!$(e.target).closest('.reaction-container').length) {
                $reactionOptions.hide();
                $reactionSummary.hide();
            }
        });

        // Set a new reaction
        function setReaction(reaction) {
            // Remove previous reaction if exists
            if (state.currentReaction) {
                state.reactionCounts[state.currentReaction]--;
            }

            // Add new reaction
            state.reactionCounts[reaction]++;
            state.currentReaction = reaction;

            // Update UI immediately
            updateCurrentReactionUI();
            updateReactionCounter();

            // Call API
            apiService.setReaction(topicType, topicId, likeUnlikeMethod, reaction)
                .catch(error => {
                    // Rollback UI changes if API fails
                    state.reactionCounts[reaction]--;
                    if (state.currentReaction === reaction) {
                        state.currentReaction = null;
                    }
                    updateCurrentReactionUI();
                    updateReactionCounter();
                });
        }

        // Remove current reaction
        function removeReaction() {
            var currentReaction = state.currentReaction;
            if (state.currentReaction) {
                state.reactionCounts[state.currentReaction]--;
                const removedReaction = state.currentReaction;
                state.currentReaction = null;

                // Update UI immediately
                updateCurrentReactionUI();
                updateReactionCounter();

                // Call API
                apiService.removeReaction(topicType, topicId, likeUnlikeMethod, currentReaction)
                    .catch(error => {
                        console.error('[ReactionsModule] Error removing reaction:', error);
                        // Rollback UI changes if API fails
                        state.reactionCounts[removedReaction]++;
                        state.currentReaction = removedReaction;
                        updateCurrentReactionUI();
                        updateReactionCounter();
                    });
            }
        }

        // Update current reaction UI based on state
        function updateCurrentReactionUI() {
            if (state.currentReaction) {
                $reactionIcon.html(`<img src="/1/image/twemoji/14.0.2/assets/svg/${emojiMap[state.currentReaction]}.svg" width="24" height="24">`);
                $reactionLabel.text(labelMap[state.currentReaction]);
                $reactionButton.addClass('selected-reaction');
            } else {
                $reactionIcon.html(`<img src="/1/image/twemoji/14.0.2/assets/svg/${emojiMap.defaultlike}.svg" width="24" height="24">`);
                $reactionLabel.text('Like');
                $reactionButton.removeClass('selected-reaction');
            }
        }

        function updateReactionCounter() {
            // Calculate total count and active reactions
            const totalCount = Object.values(state.reactionCounts).reduce((a, b) => a + b, 0);
            const activeReactions = Object.entries(state.reactionCounts)
                .filter(([_, count]) => count > 0)
                .sort((a, b) => b[1] - a[1]);

            // Update counter visibility
            if (totalCount > 0) {
                $reactionCounter.show();

                // Update counter icons (show up to 3 most popular reactions)
                $reactionCounterIcons.empty();
                const reactionsToShow = activeReactions.slice(0, 3);

                reactionsToShow.forEach(([reaction]) => {
                    $reactionCounterIcons.append(
                        `<div class="reaction-counter-icon" style="background-image: url('/1/image/twemoji/14.0.2/assets/svg/${emojiMap[reaction]}.svg')"></div>`
                    );
                });

                // Update counter text
                $reactionCounterCount.text(totalCount);
            } else {
                $reactionCounter.hide();
            }

            // Update summary counts
            $container.find('.reaction-summary-item').each(function() {
                const reaction = $(this).data('reaction');
                const count = state.reactionCounts[reaction];
                $(this).find('.reaction-summary-count').text(count);
                $(this).toggle(count > 0);
            });
        }

        // Initialize UI
        updateCurrentReactionUI();
        updateReactionCounter();

        // Return cleanup function
        return function() {
            $(document).off('click.reaction-' + topicType + '-' + topicId);
        };
    }

    // Public API
    return {
        /**
         * Initialize reactions for a topic
         * @param {HTMLElement} container - The container element where reactions should be rendered
         * @param {Object} options - Configuration options
         * @param {string} options.topicType - Unique type for the topic
         * @param {string} options.topicId - Unique identifier for the topic
         * @param {Object} [options.initialReactions] - Initial reaction counts
         * @param {string} [options.currentReaction] - Current user's reaction
         */
        init: function(container, options) {
            if (!container) {
                console.error('[ReactionsModule] Container element is required');
                return;
            }

            if (!options || !options.topicType) {
                console.error('[ReactionsModule] topicType is required in options');
                return;
            }

            if (!options || !options.topicId) {
                console.error('[ReactionsModule] topicId is required in options');
                return;
            }

            // Create and append the reaction HTML
            const reactionHTML = createReactionHTML(options.floatToolbarDirection);
            $(container).append(reactionHTML);

            // Initialize state
            const state = {
                reactionCounts: {
                    like: 0,
                    celebrate: 0,
                    support: 0,
                    love: 0,
                    insightful: 0,
                    curious: 0,
                    ...options.initialReactions
                },
                currentReaction: options.currentReaction || null
            };

            // Bind event handlers and return cleanup function
            return bindEvents(container, options.topicType, options.topicId, options.likeUnlikeMethod, state);
        },

        /**
         * Configure the API service
         * @param {Object} service - Object with getReactions, setReaction, and removeReaction methods
         */
        setApiService: function(service) {
            apiService = {
                ...apiService,
                ...service
            };
        },

        /**
         * Get the current API service configuration
         */
        getApiService: function() {
            return apiService;
        }
    };
})();

// Make it available globally if needed
window.ReactionsModule = ReactionsModule;
