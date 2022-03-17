<template>
	<view class="content">
		<image class="logo" src="/static/logo.png" @tap="spreadMenu"></image>
		<view class="text-area">
			<text class="title" @tap="spreadMenu">{{title}}</text>
			
			<dropmenu @close="showMenu = false" :show="showMenu" :coordinate="menuCoor" :list="menuList" />	
		</view>
	</view>
</template>

<script>
	import dropmenu from '@/components/dropmenu.vue';


	export default {
		components: { dropmenu }, 
		data() {
			return {
				title: '点我看看会不会有弹窗',
				showMenu: false,
				menuCoor: null,
				menuList: [
					"写跟进",
					"录订单",
					"新建回款",
					"新建任务",
					"发起工单",
					"关注",
					"新建联系人",
				],
			}
		},
		onLoad() {

		},
		methods: {
			spreadMenu(e) {
				console.log(e)
				let { x, y} = e.detail 
				this.showMenu = true
				this.menuCoor = { x, y: y + 40 }
				console.log(this.menuCoor.x, this.menuCoor.y)
			},
			closeMenu() {
				this.menuCoor = null
				this.showMenu = false
			}
		}
	}
</script>

<style>
	.content {
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
	}

	.logo {
		height: 200rpx;
		width: 200rpx;
		margin-top: 200rpx;
		margin-left: auto;
		margin-right: auto;
		margin-bottom: 50rpx;
	}

	.text-area {
		display: flex;
		justify-content: center;
	}

	.title {
		font-size: 36rpx;
		color: #8f8f94;
	}
</style>
