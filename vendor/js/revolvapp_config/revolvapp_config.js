$(function() {
    Revolvapp.add('block', 'block.text-highlight', {
        mixins: ['block'],
        type: 'text-highlight',
        title: 'Text with highlight',
        section: 'one',
        build: function() {

            // create a column
            let column = this.app.create('tag.column', {
                'width': '100%',
                'padding': '5px 10px',
                'background-color': '#666666'
            });

            // Add text block to column
            let text = this.app.create('tag.text', {
                html: this.lang.get('placeholders.lorem'),
                color: '#ffffff'
            });
            column.add(text);

            // create a grid, add column to grid
            let grid = this.app.create('tag.grid', {});
            grid.add(column);

            let spacer = this.app.create('tag.spacer', {
                'height': '5'
            });

            let inner_block = this.app.create('tag.block', {
                'padding': '10px 0'
            });
            inner_block.add(grid);

            // Add grid to the main block
            this.block = this.app.create('tag.block', {});
            this.block.add(inner_block);


        }
    });
});