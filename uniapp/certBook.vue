<template>
	<view>
		<uni-list v-for="(img, year) in xfk" :key="year" :border="true">
			<uni-list-chat :title="year + '年学分证明'" :avatar="img">
				<view class="chat-custom-right">
					<view class="chat-custom-right-item" @tap="previewImage(img)">
						<uni-icons type="eye-filled" color="#999" size="18"></uni-icons>
						<text class="chat-custom-text">预览</text>
					</view>
					<view class="chat-custom-right-item" @tap="downloadImage(img)">
						<uni-icons type="download-filled" color="#999" size="18"></uni-icons>
						<text class="chat-custom-text">下载</text>
					</view>
				</view>
			</uni-list-chat>
		</uni-list>	
	</view>
</template>

<script>
	import uniIcons from '@/components/uniui/uni-icons/uni-icons.vue';
	import uniList from '@/components/uniui/uni-list/uni-list.vue';
	import uniListItem from '@/components/uniui/uni-list-item/uni-list-item.vue';
	import uniListChat from '@/components/uniui/uni-list-chat/uni-list-chat.vue';

	import {
		get_xfk
	} from '@/api/user'
	export default {
		components: { uniList, uniListItem, uniListChat, uniIcons },
		data() {
			return {
				src: '',
				xfk: null,
			};
		},
		onLoad() {
			get_xfk().then(ret => {
				this.xfk = ret.data.data;
			})
		},
		methods: {
			previewImage(img) {
				uni.previewImage({
					urls: [img],
					longPressActions: {
						itemList: ['发送给朋友', '保存图片', '收藏'],
						success: function(data) {
							console.log('选中了第' + (data.tapIndex + 1) + '个按钮,第' + (data.index + 1) +
								'张图片');
						},
						fail: function(err) {
							console.log(err.errMsg);
						}
					}
				});
			},
			async downloadImage(img) {
				try {
					let auth = await uni.authorize({ scope: 'scope.writePhotosAlbum' });
					if (auth[0]) {
						let modalRes = await uni.showModal({
							title: '无法保存图片到相册',
							content: '请点击允许相册访问权限',
						});
						if (modalRes[1].cancel) {
							throw new Error("拒绝授权，保存失败！");	
						} else {
							let setRes = await uni.openSetting()
							let auth = setRes[1].authSetting["scope.writePhotosAlbum"];
							if (!auth) throw new Error("拒绝授权，保存失败！");	
						}
					}
					let res = await uni.downloadFile({ url: img });	
					console.log("文件下载信息", res)
					if (res[1].statusCode !== 200) throw new Error("下载图片失败！");
					await uni.saveImageToPhotosAlbum({ filePath: res[1].tempFilePath });		
				} catch (error) {
					console.log("下载学分卡失败", error);
					let msg = error.message.indexOf("失败") === -1 ? "下载图片失败！" : error.message;
					uni.showToast({ title: msg, icon: 'none'});
				}
			}
		},
	};
</script>

<style>
	.chat-custom-right {
		flex: 1;
		display: flex;
	}
	.chat-custom-right-item {
		flex: 1;
		display: flex;
		flex-direction: column;
		justify-content: space-between;
		align-items: flex-end;
		padding-right: 8px;
		padding-left: 8px;
	}
	.chat-custom-text {
		font-size: 12px;
		color: #999;
	}
	/deep/.uni-list-chat__content-note {
		display: none;
	}
	/deep/.uni-list-chat__content-main {
		justify-content: center!important;
	}

</style>
