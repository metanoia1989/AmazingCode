<template>
	<view v-if="coordinate">
		<view v-if="show" class="mask" @tap="close"></view>
		<view  :class="['menu-container',  (show && coordinate) ? 'container-open' : 'container-close']" :style="{ left: coordinate.x + 'px', top: coordinate.y + 'px' }">
			<view class="menu-margin">
				<view :class="['menu-item',  (show && coordinate) ? 'item-open' : 'item-close']" v-for="item in list" :key="item">{{ item }}</view>	
			</view>
		</view>
	</view>
</template>

<script>
	/**
	 * Dropmenu 下拉菜单
	 */
	export default {
		name:"dropmenu",
		props: {
			list: {
				type: Array,
				default: () => [],
			},
			show: {
				type: Boolean,
				default: false,
			},
			coordinate: {
				type: Object,
				default: () => {
					return { x: 0, y: 0 }
				},
			},
		},
		computed: {
		},
		data() {
			return {
				
			};
		},
		methods: {
			close() {
				this.$emit('close')
			},
		},
	}
</script>

<style>
	.mask {
		position: fixed;
		top: 0;
		bottom: 0;
		left: 0;
		right: 0;
		background-color: transparent;
		z-index: 1;
	}
	.menu-container {
		background-color: #fff;
		box-shadow: 1px 1px 5px rgba(0, 0, 0, 0.3);
		border-radius: 10px;
		position: fixed;
		z-index: 1000;
		overflow: hidden;
		box-sizing: border-box;
	}
	.menu-margin {
		margin: 15px 15px;
	}
	.menu-item {
		height: 30px;
	}


	.container-open {
		height: 400rpx;
		transition: height 0.4s cubic-bezier(0.25, 1.0, 0.25, 1.0);
	}
	.container-close {
		height: 0;
		transition: height 0.4s cubic-bezier(0.25, 1.0, 0.25, 1.0);
	}

	.item-open {
		height: 30px;
		transition: height 0.4s cubic-bezier(0.25, 1.0, 0.25, 1.0);
	}
	.item-close {
		opacity: 0;
		height: 0;
	}
</style>
