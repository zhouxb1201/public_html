<template>
  <div class="choice-address">
    <div class="address-tab">
      <van-cell
        border
        center
        is-link
        class="address-card card-bottom-bg"
        title="添加收货地址"
        icon="add-o"
        @click="onAddress"
        v-if="postType == 'add'"
      />
      <van-cell
        border
        center
        is-link
        class="address-card card-bottom-bg"
        icon="location-o"
        @click="onAddress"
        v-else
      >
        <div class="address-info">
          <div class="user">
            <van-col span="14">{{address.name}}</van-col>
            <van-col span="10" class="text-right">{{address.tel}}</van-col>
          </div>
          <div class="address">
            <van-col span="24">{{address.address}}</van-col>
          </div>
        </div>
      </van-cell>
    </div>
    <van-popup v-model="showList" position="bottom" class="address-popup">
      <van-nav-bar
        title="收货地址"
        left-text="返回"
        left-arrow
        fixed
        :z-index="999"
        @click-left="showList = false"
      />
      <van-address-list v-model="addressId" :list="list" @add="onAdd" @select="onSelect"/>
    </van-popup>
    <van-popup
      v-model="showEdit"
      :close-on-click-overlay="false"
      position="bottom"
      class="address-popup"
    >
      <van-nav-bar
        title="添加地址"
        left-text="返回"
        left-arrow
        fixed
        :z-index="999"
        @click-left="showEdit = false"
      />
      <van-address-edit
        :area-list="areaList"
        show-postal
        show-set-default
        @save="onSave"
        :is-saving="is_saving"
        @change-area="onChangeArea"
      />
    </van-popup>
  </div>
</template>

<script>
import { isEmpty } from "@/utils/util";
import { GET_ADDRESSLIST, SAVE_ADDRESS } from "@/api/address";
import { NavBar, AddressList, AddressEdit } from "vant";
export default {
  data() {
    return {
      showList: false,

      list: [],

      showEdit: false,

      params: {},
      is_saving: false
    };
  },
  props: {
    address: Object
  },
  computed: {
    postType() {
      return isEmpty(this.address) ? "add" : "edit";
    },
    addressId: {
      get() {
        return !isEmpty(this.address) ? this.address.id : 0;
      },
      set(value) {
        // console.log(value)
      }
    },
    areaList() {
      return this.$store.getters.areaList;
    }
  },
  methods: {
    onAddress() {
      const $this = this;
      const list = $this.list;
      $this.showList = true;
      if (isEmpty(list)) $this.getAddressList();
    },
    getAddressList() {
      const $this = this;
      return new Promise((resolve, reject) => {
        const list = [];
        GET_ADDRESSLIST({
          page_index: 1,
          page_size: 20
        }).then(({ data }) => {
          const arr = data.address_list ? data.address_list : [];
          arr.forEach(e => {
            let obj = {};
            obj.id = e.id;
            obj.name = e.consigner;
            obj.tel = e.mobile;
            obj.address =
              e.province_name + e.city_name + e.district_name + e.address;
            obj.province = e.province;
            obj.city = e.city;
            obj.district = e.district;
            obj.area_code = e.area_code;
            list.push(obj);
          });
          $this.list = list;
          resolve(list);
        });
      });
    },

    onAdd() {
      const $this = this;
      if (isEmpty($this.areaList)) {
        $this.$store.dispatch("getArea", true).then(() => {
          $this.showEdit = true;
        });
      } else {
        $this.showEdit = true;
      }
    },

    onSelect(item, index) {
      if (this.showList) {
        this.$emit("select", item);
        this.showList = false;
      }
    },

    onChangeArea(e) {
      const $this = this;
      $this.params.province = $this.areaList.province_id_list[e[0].code];
      $this.params.city = $this.areaList.city_id_list[e[1].code];
      $this.params.district = $this.areaList.county_id_list[e[2].code];
    },

    onSave(data) {
      const $this = this;
      $this.params.id = data.id ? data.id : "";
      $this.params.province = data.province_id
        ? data.province_id
        : $this.params.province;
      $this.params.city = data.city_id ? data.city_id : $this.params.city;
      $this.params.district = data.county_id
        ? data.county_id
        : $this.params.district;
      $this.params.consigner = data.name;
      $this.params.mobile = data.tel;
      $this.params.address = data.addressDetail;
      $this.params.is_default = data.isDefault ? 1 : 0;
      $this.params.area_code = data.areaCode;
      $this.params.zip_code = data.postalCode;
      $this.is_saving = true;
      // console.log($this.params);
      // return;
      SAVE_ADDRESS($this.params)
        .then(({ data, message }) => {
          $this.$Toast.success(message);
          $this.getAddressList().then(list => {
            const currItem = list.filter(({ id }) => id == data.id)[0];
            $this.onSelect(currItem);
            $this.is_saving = false;
            $this.showEdit = false;
          });
        })
        .catch(() => {
          $this.is_saving = false;
        });
    }
  },
  components: {
    [NavBar.name]: NavBar,
    [AddressList.name]: AddressList,
    [AddressEdit.name]: AddressEdit
  }
};
</script>

<style scoped>
.van-address-list >>> .van-radio-group {
  max-height: calc(100vh - 122px);
  overflow-y: auto;
}

.address-card {
  padding: 15px;
  background: #fff;
}

.address-info .user {
  font-size: 14px;
  font-weight: 500;
  line-height: 20px;
  margin-bottom: 5px;
  overflow: hidden;
}

.address-info .address {
  font-size: 12px;
  line-height: 16px;
  color: #666;
  overflow: hidden;
}

.address-card >>> .van-cell__left-icon {
  color: #9eabbd;
  font-size: 24px;
  margin-right: 10px;
}

.address-card >>> .van-cell__title {
  line-height: 30px;
}

.address-popup {
  height: 100%;
  padding-top: 46px;
  border-radius: 0;
}

.card-bottom-bg::before {
  content: "";
  left: 0;
  right: 0;
  bottom: 0;
  height: 2px;
  position: absolute;
  background: -webkit-repeating-linear-gradient(
    135deg,
    #ff6c6c 0,
    #ff6c6c 20%,
    transparent 0,
    transparent 25%,
    #3283fa 0,
    #3283fa 45%,
    transparent 0,
    transparent 50%
  );
  background: repeating-linear-gradient(
    -45deg,
    #ff6c6c 0,
    #ff6c6c 20%,
    transparent 0,
    transparent 25%,
    #3283fa 0,
    #3283fa 45%,
    transparent 0,
    transparent 50%
  );
  background-size: 80px;
}

.choice-address >>> .van-address-item__edit {
  display: none;
}
</style>
