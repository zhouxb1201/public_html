<template>
    <div class="audio-box">
        <div class="audio-img"><img :src="data.goods_picture" /></div>
        <div class="audio-content" ref="audioContent">
            <audio ref="audio" @pause="onPause" @play="onPlay" @timeupdate="onTimeupdate" @loadedmetadata="onLoadedmetadata" :src="data.content" controls="controls" style="display:none;"></audio>

            <!-- 音频播放控件 -->
            <div>
                <div class="title">
                    <van-tag class="tag" round size="medium" color="#FAE9E6" text-color="#ff454e" v-if="data.is_see > 0 && !data.is_buy">试学{{data.is_see}}分钟</van-tag>
                    {{data.name}}
                </div>
                <div class="slider" @touchstart="handleTouchStart">
                    <div class="slider-track"></div>
                    <div class="slider-fill" :style="'width:'+sliderTime+'%'"></div>
                    <div class="slider-thumb" :style="'left:'+sliderTime+'%'"></div>
                </div>
                <div class="flex-pack-justify">
                    <div class="fs-10">{{ audio.currentTime | formatSecond}}</div>
                    <div class="startPlayOrPause">
                        <a @click="startPlayOrPause">
                            <van-icon :name="audio.playing | transPlayPause" size="28px" color="#ff454e" />
                        </a>
                    </div>
                    <div class="pr-12 fs-10">{{ audio.maxTime | formatSecond}}</div>
                </div>
            </div>
        </div>
        <div class="audio-overlay" v-show="audioOverlayShow">
            <div class="audio-overlay-text">试学结束，购买后查看完整版</div>
                <van-button
                  type="danger"
                  size="small"
                  :to="'/goods/detail/'+ goodsId"
                >立即购买</van-button>
        </div>
    </div>
</template>

<script>
// 将整数转换成 时：分：秒的格式
function realFormatSecond(second) {
    var secondType = typeof second

    if (secondType === 'number' || secondType === 'string') {
        second = parseInt(second)

        var hours = Math.floor(second / 3600)
        second = second - hours * 3600
        var mimute = Math.floor(second / 60)
        second = second - mimute * 60

        return hours + ':' + ('0' + mimute).slice(-2) + ':' + ('0' + second).slice(-2)
    } else {
        return '0:00:00'
    }
}

export default {
    data() {
        return {
            sliderTime: 0,
            audio: {
                // 该字段是音频是否处于播放状态的属性
                playing: false,
                // 音频当前播放时长
                currentTime: 0,
                // 音频最大播放时长
                maxTime: 0,
                minTime: 0,
                step: 0.1
            },
            audioOverlayShow:false,
        }
    },
    props: {
        data: [Object, Array],
        goodsId:[String, Number],
    },
    mounted(){
        this.isSee();
    },
    methods: {
        // 控制音频的播放与暂停
        startPlayOrPause() {
            return this.audio.playing ? this.pause() : this.play()
        },
        // 播放音频
        play() {
            this.$refs.audio.play()
        },
        // 暂停音频
        pause() {
            this.$refs.audio.pause()
        },
        // 当音频播放
        onPlay() {
            this.audio.playing = true
        },
        // 当音频暂停
        onPause() {
            this.audio.playing = false
        },
        handleFocus() {
            // console.log('focues')
        },
        // 当加载语音流元数据完成后，会触发该事件的回调函数
        // 语音元数据主要是语音的长度之类的数据
        onLoadedmetadata(res) {
            this.audio.maxTime = parseInt(res.target.duration);
        },
        // 当timeupdate事件大概每秒一次，用来更新音频流的当前播放时间
        // 当音频当前时间改变后，进度条也要改变
        onTimeupdate(res) {
            this.audio.currentTime = res.target.currentTime
            this.sliderTime = parseInt(this.audio.currentTime / this.audio.maxTime * 100)
        },

        // 进度条格式化toolTip
        formatProcessToolTip(index = 0) {
            index = parseInt(this.audio.maxTime / 100 * index)
            return '进度条: ' + realFormatSecond(index)
        },

        handleTouchStart(e) {
            this.setValue(e.touches[0]);

            document.addEventListener('touchmove', this.handleTouchMove);
            document.addEventListener('touchup', this.handleTouchEnd);
            document.addEventListener('touchend', this.handleTouchEnd);
            document.addEventListener('touchcancel', this.handleTouchEnd);

            // e.preventDefault();
            // this.onDragStart(e);
        },
        handleTouchMove(e) {
            this.setValue(e.changedTouches[0]);
        },
        handleTouchEnd(e) {
            this.setValue(e.changedTouches[0]);
            document.removeEventListener('touchmove', this.handleTouchMove);
            document.removeEventListener('touchup', this.handleTouchEnd);
            document.removeEventListener('touchend', this.handleTouchEnd);
            document.removeEventListener('touchcancel', this.handleTouchEnd);
            // this.onDragStop(e);
        },
        // 从点击位置更新 value
        setValue(e) {
            // const $el = this.$el;
            const $el = this.$refs.audioContent;
            const {
                maxTime,
                minTime,
                step
            } = this.audio;
            let value = (e.clientX - $el.getBoundingClientRect().left) / $el.offsetWidth * (maxTime - minTime);
            value = Math.round(value / step) * step + minTime;
            value = parseFloat(value.toFixed(5));

            if (value > maxTime) {
                value = maxTime;
            } else if (value < minTime) {
                value = minTime;
            }
            this.$refs.audio.currentTime = value;
        },
        // 拖动进度条，改变当前时间，index是进度条改变时的回调函数的参数0-100之间，需要换算成实际时间
        changeCurrentTime(index) {
            this.$refs.audio.currentTime = parseInt(index / 100 * this.audio.maxTime)
        },
        isSee() {
            if(!this.data.is_buy && this.data.is_see == -1){
                this.audioOverlayShow = true;
            }
        }
    },
    filters: {
        // 使用组件过滤器来动态改变按钮的显示
        transPlayPause(value) {
            return value ? 'pause-circle-o' : 'play-circle-o'
        },
        // 将整数转化成时分秒
        formatSecond(second = 0) {
            return realFormatSecond(second)
        }
    },
    watch:{
        'audio.currentTime':{
            handler(newval,oldval){
                let seconds = this.data.is_see * 60;
                if(!this.data.is_buy &&  this.data.is_see>0){
                    if(newval>seconds){
                        this.pause();
                        this.audioOverlayShow = true;
                    }
                }
            }
        },
        'data.is_see':{
            handler(newval,oldval){
                if(newval>0){
                    this.audioOverlayShow = false;
                }else if(!this.data.is_buy && newval == -1){
                    this.audioOverlayShow = true;
                }else{
                    this.audioOverlayShow = false;
                }
            }
        },
    },
}
</script>

<style scoped>
.slider {
    margin-top: 6px;
    width: 95%;
    position: relative;
    height: 24px;
    display: flex;
    align-items: center;
    cursor: default;
    user-select: none;
    outline: none;
}

.slider-track {
    position: absolute;
    height: 2px;
    left: 0;
    right: 0;
    top: 50%;
    margin-top: -1px;
    background-color: #bec2c1;
}

.slider-fill {
    position: absolute;
    height: 2px;
    width: 100%;
    background-color: #ff454e;
    left: 0;
    top: 50%;
    margin-top: -1px;
}

.slider-thumb {
    position: absolute;
    top: 50%;
    width: 12px;
    height: 12px;
    background-color: #ff454e;
    color: #ff454e;
    border-radius: 50%;
    transform: translate(-50%, -50%);
    cursor: pointer;
}

.audio-box {
    display: flex;
    background-color: #fff;
    margin: 10px 0;
    position: relative;
}

.audio-box .audio-img {
    position: relative;
    width: 100px;
    height: 100px;
    margin: 10px;
    -webkit-box-flex: 0;
    -ms-flex: none;
    flex: none;
    background: #f9f9f9;
    display: -webkit-box;
    display: -ms-flexbox;
    display: flex;
    -webkit-box-align: center;
    -ms-flex-align: center;
    align-items: center;
    -webkit-box-pack: center;
    -ms-flex-pack: center;
    justify-content: center;
}

.audio-box .audio-img img {
    border: 0;
    max-width: 100%;
    max-height: 100%;
}

.audio-box .audio-content {
    width: 100%;
    margin-top: 22px;
}

.audio-box .title {
    line-height: 16px;
    max-height: 35px;
    font-weight: 700;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    word-break: break-all;
}

.flex-pack-justify {
    display: -webkit-box;
    display: -webkit-flex;
    display: -ms-flexbox;
    display: flex;
    -webkit-box-pack: justify;
    -webkit-justify-content: space-between;
    -ms-flex-pack: justify;
    justify-content: space-between;
}

.pr-12 {
    padding-right: 12px;
}

.startPlayOrPause {
    position: relative;
    top: -5px;
}
.audio-box .audio-overlay{
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,.7);
    color: #fff;
    text-align: center;
    padding-top: 25px;
}
.audio-box .audio-overlay .audio-overlay-text{
    margin-bottom: 10px;
}
.fs-10{
    font-size: 10px;
}
</style>