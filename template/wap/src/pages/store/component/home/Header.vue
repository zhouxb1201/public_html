<template>
  <div class="head">
    <HeadBanner class="banner" :src="_info.images[0] | BASESRC" />
    <van-cell-group class="cell-group card-group-box">
      <van-cell class="cell-item" :border="false" :title="_info.title" />
      <van-cell class="cell-item info-item" :border="false">
        <div slot="icon" class="img-box">
          <img :src="_info.images[0] | BASESRC" :onerror="$ERRORPIC.noSquare" @click="onPreview" />
          <span class="num">{{_info.images.length}}</span>
        </div>
        <Star slot="title" :value="_info.score" />
        <div slot="label" class="label" @click="showMap=true">
          <van-icon name="location-o" class="icon" />
          <span>{{_info._address}}</span>
        </div>
      </van-cell>
      <van-cell class="cell-item" :border="false">
        <div class="text-regular">{{_info.time}}</div>
        <a :href="_info.tel" slot="right-icon">
          <van-icon name="v-icon-phone" size="16px" />
        </a>
      </van-cell>
    </van-cell-group>
    <MapNavigator v-model="showMap" :params="navigatorInfo" />
  </div>
</template>

<script>
import HeadBanner from "@/components/HeadBanner";
import Star from "@/components/Star";
import { ImagePreview } from "vant";
import MapNavigator from "@/components/MapNavigator";
export default {
  data() {
    return {
      showMap: false
    };
  },
  props: {
    info: {
      type: Object
    }
  },
  computed: {
    _info() {
      let info = this.info;
      info.title = `${info.shop_name} (${info.store_name})`;
      info._address = `${info.province_name}${info.city_name}${info.district_name}${info.address}(距您${info.distance}km)`;
      info.tel = `tel:${info.store_tel}`;
      info.time = `营业时段 ${info.start_time} - ${info.finish_time}`;
      info.images = info.store_img || [];
      return info;
    },
    navigatorInfo() {
      let { lng, lat, store_name } = this.info;
      return {
        lng,
        lat,
        name: store_name
      };
    }
  },
  methods: {
    onPreview() {
      ImagePreview(this._info.images);
    }
  },
  components: {
    HeadBanner,
    Star,
    [ImagePreview.name]: ImagePreview,
    MapNavigator
  }
};
</script>

<style scoped>
.head {
  position: relative;
  overflow: hidden;
  padding-bottom: 30px;
}

.head .banner {
  height: 150px;
  position: absolute;
  top: 0;
  left: 0;
}

.head .banner >>> img {
  filter: blur(10px);
}

.head .cell-group {
  position: relative;
  top: 15px;
}

.info-item {
  padding: 0 15px;
}

.label {
  height: 36px;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

.label .icon {
  font-size: 14px;
  vertical-align: middle;
}

.img-box {
  width: 63px;
  height: 63px;
  position: relative;
  margin-right: 10px;
}

.img-box img {
  width: 100%;
  height: 100%;
  display: block;
}

.img-box .num {
  position: absolute;
  display: block;
  width: 20px;
  height: 20px;
  line-height: 20px;
  text-align: center;
  background: rgba(0, 0, 0, 0.3);
  border-radius: 50%;
  color: #fff;
  font-size: 10px;
  bottom: 0;
  right: 0;
}
</style>